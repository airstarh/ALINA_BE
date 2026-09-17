# KeenDNS chat: first configuration pass

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
