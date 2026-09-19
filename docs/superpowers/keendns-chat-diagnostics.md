# KeenDNS chat: first configuration pass

## Latest pass after Firefox timeout at 18:48

Read-only review of deployed files under `/mnt/sshfs/bbb/mnt/d1001/_docker/az/ALINA_BE/` and SSH bbb at 18:54 found direct health 200 in 0.0085 seconds and cloud health 200 in 0.657 seconds. PHP responses around 18:48:10–11 completed with 200 in 0.105 and 0.040 seconds. These do not identify which browser produced them; static requests are excluded from timing logs. The later screenshot timeout is not conclusively attributed.

Concrete separate failure: deployed nginx error.log recorded `host not found in upstream "php82chat"` at 18:03:13 during stack startup. Backend names were resolved at config load, so an absent chat service could prevent the entire nginx process from starting. Embedded Docker DNS was confirmed as 127.0.0.11 in the container.

Local-only fix in `server/etc/nginx/conf.d/location.alina.php82`: configure resolver 127.0.0.11 with 10-second validity and 5-second resolution timeout; use variable destinations for chat, signaling and FastCGI PHP so resolution happens at request time. No URI suffix is supplied to proxy_pass, preserving incoming request paths and query strings. The patch is intended for this Docker network; deployments outside Docker need their own resolver. A missing backend still makes its requests fail, but no longer blocks nginx configuration loading. Address refresh can have a short cache delay.

Local nginx 1.20.2 syntax check and git diff check passed. Runtime routing after this change has not been exercised because no reload/restart was performed. Deploy the updated shared location, validate configuration, then reload/restart as desired and check health, page/API, /ws and signaling. Remote files remain unchanged by Codex.

Chat logs still show many upgraded sessions ending at roughly 30 seconds with retries about 42 seconds apart. A green connected indicator in a screenshot cannot establish uninterrupted connectivity. This remains a separate investigation from ordinary page-loading failures; nginx's 3600-second idle setting does not explain 30-second closures.

Target: `https://azo.zadobro.crazedns.ru/apps/vue/chat` through KeenDNS cloud access, HTTPS upstream on LAN port 50443.

## Findings and limits

The shared `/ws` location previously inherited a 300-second upstream idle read timeout from the HTTPS default server. The HTTP server and other virtual hosts without overrides inherited nginx's 60-second default. Chat's WsServer currently does not enable periodic WebSocket pings. Silent connections can therefore expire even when the page is still open.

The public URL timed out after 15 seconds from the development environment, including a probe outside sandbox networking. This did not identify the failing network hop or reproduce an established WebSocket disconnect. The changes below are a configuration improvement and diagnostic pass, not a confirmed fix for every reported instability.

## Changes to deploy

- `server/etc/nginx/nginx.conf`: defines a chat log format with host, path, handshake headers, HTTP/upstream status and connection/request timing. Does not log query strings, cookies or authentication headers.
- `server/etc/nginx/conf.d/location.alina.php82`: sets WebSocket read/send inactivity limits to 3600 seconds, turns on upstream TCP keepalive, disables proxy buffering/cache, and writes `/var/log/nginx/chat.access.log`.
- Deploy both files together: the location references the log format defined in nginx.conf.
- These changes apply to all sites including this shared location, and only tune `/ws`. They do not increase PHP timeouts, alter certificates, or change socket.io behavior.

After deployment, run `nginx -t` in the host's nginx container, then reload nginx if the check succeeds. No local reload or container restart was performed by Codex.

## Next feedback to collect

1. Note whether the page itself fails to load, or the page loads and chat repeatedly reconnects. These are different failures.
2. Note the exact time of a disconnect and how long the connection was idle.
3. After disconnect, collect the matching `chat.access.log` line and nginx error-log entries. A WebSocket access-log line is written when its connection ends. Status 101 indicates the upgrade succeeded; it does not prove the later session was healthy. Missing lines can mean traffic never reached nginx or the connection is still open.
4. Compare the same chat directly on the LAN with the cloud domain. Check the browser's WebSocket request URL: it should use the public host, not zero.home or a LAN-only hostname.

## Router and future heartbeat

The screenshot's azo upstream is HTTPS on 192.168.1.120:50443. Keep that working mapping for this first pass. Verify the LAN address is reserved and that the router can consistently reach that HTTPS endpoint. Do not enable router-management access merely to troubleshoot the application's cloud mapping.

No verified KeenDNS cloud WebSocket timeout control was found. The documented `cloud control client ... session timeout` command concerns cloud-control client sessions; do not assume it configures application WebSocket tunnels. Router CLI changes need documentation matching the installed KeeneticOS version.

If disconnects persist during idle periods, the next targeted change is protocol-level server pings using Ratchet WsServer's existing `enableKeepAlive($loop, 30)` method. TCP keepalive in this nginx patch is not a WebSocket ping and cannot guarantee that a cloud proxy keeps its application tunnel open. Enabling pings requires deploying the runner and restarting the chat worker; its in-memory history would be lost on restart.

References: https://nginx.org/en/docs/http/websocket.html and https://nginx.org/en/docs/http/ngx_http_proxy_module.html#proxy_socket_keepalive.

## Second pass: whole-site availability

User deployed the first pass and restarted the stack, then clarified that ordinary site availability also fails periodically. Read-only checks on `bbb` found the following:

- Direct HTTPS at 127.0.0.1:50443 with the azo Host header returned 200 in 0.108 seconds.
- A public KeenDNS request from bbb returned 200 in 0.918 seconds. A prior probe from the development machine timed out, so network vantage matters.
- Nginx reported OOMKilled=false. Historical upstream unreachable errors concern /ws; they do not establish current whole-site failure.
- New chat log entries after deployment show successful upgrades ending at 30.055, 30.078 and 30.410 seconds, despite the new 3600-second nginx idle timeout. This suggests a different layer or client closes these sessions, but is not definitive attribution to KeenDNS.
- Cloud traffic arrives with Host 192.168.1.120, suggesting the router rewrites the Host header. Default-server selection currently still serves the site. Future virtual-host routing should account for this.
- PHP pool configuration has seven workers and four-hour termination limits. Slow requests can exhaust workers; no worker exhaustion was established in this inspection. Do not arbitrarily increase worker counts without checking memory and request behavior.

Second-pass local changes add `/_alina/health` returning `nginx alive` without invoking PHP, and `/var/log/nginx/alina.timing.log` for ordinary page/dynamic requests. Existing access-log destinations are preserved. Static asset locations with access_log off still do not generate these timings. Query strings, credentials and bodies are omitted.

Deploy these THREE files together: nginx.conf, conf.d/default.conf, and conf.d/location.alina.php82. Local `nginx -t` passed; no remote configuration changes/reloads/restarts were performed by Codex.

When the site next fails, compare:

1. Public `https://azo.zadobro.crazedns.ru/_alina/health`.
2. Public `https://azo.zadobro.crazedns.ru/apps/vue/chat`.
3. On bbb, the same paths through `https://127.0.0.1:50443` with `Host: azo.zadobro.crazedns.ru`.

Public health failure with healthy direct-LAN health points toward the cloud/router/network path. Healthy public health with failed app/API requests narrows investigation to routing/PHP/application/dependencies; inspect timing entries and PHP slow logs. Failed direct health calls require container/process/resource/network investigation. These are diagnostic branches, not automatic proof of a single cause.

## Keenetic support confirmation — recorded 2026-09-18

Support ticket: **293982139**. The user submitted the report and subsequently supplied the technical response below. Its receipt date was not specified; this section records the date it was added to the handoff.

Keenetic support confirms current difficulties with its cloud servers. Developers are aware and working on recovery. Support expects a fix soon but gives no definite completion time. This confirms an upstream service problem relevant to the intermittent public availability; do not assume a local ALINA configuration change will fix the cloud outage.

Earlier read-only tests found HTTP and HTTPS working through `87.228.71.67` and `178.250.154.58`, while `185.162.93.96`, `95.213.212.50`, and `5.35.2.42` timed out. The first four were tested from the LAN and the independent external server `sss`; `5.35.2.42` was tested from `sss`. Public DNS continued advertising failing addresses. These are historical observations, not permanently reliable endpoint lists.

The router DNS at `192.168.1.254` returned `198.51.100.11` for `azo.zadobro.crazedns.ru`; HTTP and certificate-verified HTTPS succeeded using that answer inside the LAN. Pi-hole on `bbb` uses Google upstreams and returned cloud addresses, including stale cached answers. A domain-specific forwarding rule to the router was proposed but **not applied**. No hosts-file override, Pi-hole/router configuration change, or ALINA change was made for this KeenDNS issue.

### Support response supplied by the user

```text
Здравствуйте.

Сейчас наблюдаются трудности в работе облачных серверов, в логе вы можете видеть следующие записи >

[E] Sep 11 21:35:12 ndm: Cloud::Tunnel::Actions: "NDNS/1ecc75b786": failed to connect to 5.35.2.42:80 (SSL): operation timeout.

[E] Sep 11 21:35:17 ndm: Io::TcpSocket: failed to connect: operation in progress.

[E] Sep 11 21:35:17 ndm: Cloud::Tunnel::Actions: "NDNS/dce811004c": failed to connect to 5.35.2.42:80 (SSL): operation timeout.

[E] Sep 11 21:35:34 ndm: Io::TcpSocket: failed to connect: operation in progress.

Разработчики в курсе данной ситуации, занимаются восстановлением.

Ожидаем исправление в ближайшее время.

T_I_C_K_E_T_I_D_293982139
```

Next session: check for further support replies, then retest current DNS answers and ordinary public HTTP/HTTPS access from the LAN and `sss` read-only. Compare with direct LAN health to distinguish cloud recovery from application behavior. Preserve the user's requirement for explicit approval before configuration changes or service/container restarts. No automatic follow-up or notifications were scheduled.
