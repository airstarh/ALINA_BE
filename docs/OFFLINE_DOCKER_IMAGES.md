# Offline Docker Images for ALINA

## Status

This document records an approved architectural recommendation for future implementation. The offline image workflow described here is not implemented yet.

## Immutable infrastructure contract

The ALINA infrastructure consists of exactly these four sibling directories:

```text
ALINA_BE
ALINA_FE
ALINA_V
STORAGE
```

This layout is a project contract and must not be reorganized. Individual hosts may disable any part of the infrastructure through the existing host configuration.

## Objective

Prepare Docker images on an internet-connected machine, store them under `STORAGE/DOCKER`, copy the ALINA infrastructure to another machine, and run enabled services without downloading images or application dependencies from the internet.

Offline rebuilding of Docker images is not the objective. The destination host only needs to import and run previously prepared images.

## Important Docker behavior

Docker cannot run an image archive directly from `STORAGE/DOCKER`. The archive is the portable source of the images.

On each destination host, an import command must load the archive into that host's Docker Engine once. Docker Compose can then start the locally loaded images with building and pulling disabled.

The intended offline startup behavior is equivalent to:

```bash
docker compose up -d --no-build --pull never
```

If a required image is absent, startup must stop with a clear error. It must not silently contact a registry.

## Recommended storage layout

```text
STORAGE/DOCKER/
├── images/
│   └── alina-images-amd64.tar.gz
├── manifest/
│   ├── images.txt
│   ├── versions.txt
│   └── SHA256SUMS
├── export.sh
├── import.sh
├── verify.sh
└── README.md
```

One combined archive is recommended because Docker can preserve shared layers in the archive. Importing unused images does not start their services and does not consume runtime memory. Separate archives remain possible if a future deployment requires them.

## Preparation workflow

On an internet-connected preparation host:

1. Pull the pinned third-party images.
2. Build all custom ALINA images.
3. Verify that every required image exists locally.
4. Save the complete image set with `docker save`.
5. Compress the resulting archive into `STORAGE/DOCKER/images`.
6. Write the exact image names, tags, versions, and CPU architecture to the manifests.
7. Generate SHA-256 checksums.

## Destination-host workflow

On a destination host:

1. Copy the complete ALINA four-directory infrastructure.
2. Run `STORAGE/DOCKER/verify.sh` to verify checksums and architecture.
3. Run `STORAGE/DOCKER/import.sh` once to load the images into Docker Engine.
4. Use the existing host configuration to select enabled services.
5. Start ALINA with image pulling and building disabled.

Docker Engine and Docker Compose must already be installed on the destination host.

## Required project adjustments

### Stable image identities

Every self-built image must receive an explicit, stable, versioned name instead of depending on an automatically generated Compose project name. Proposed identities include:

```text
alina/php-fpm:8.5.12
alina/php-fpm-xdebug:8.5.12
alina/signaling:1
```

The final names may be refined during implementation, but they must remain explicit and predictable.

### Pinned third-party images

Every third-party image must use a fixed tag. The current `certbot/certbot:latest` reference must be replaced with a specific version.

Currently identified runtime or development images include:

```text
nginx:1.20
mysql:8.0.27
node:20-alpine
certbot/certbot:latest
alexxit/go2rtc:1.9.14
ghcr.io/php-cs-fixer/php-cs-fixer:3-php8.2
```

The PHP build also starts from:

```text
php:8.5.12-fpm
```

The PHP base image and intermediate build dependencies do not need to be present on an offline runtime host when the completed ALINA PHP images have already been exported.

### Signaling service

The current signaling service performs this operation during container startup:

```text
npm install && npm run start
```

That requires network access to the npm registry and therefore prevents reliable offline startup.

The signaling dependencies from `ALINA_V/020.d/package-lock.json` must instead be installed while preparing a dedicated `alina/signaling` image. Runtime startup must execute the already prepared application without `npm install`.

### Offline enforcement

The startup tooling should:

- prohibit image pulling;
- prohibit image building;
- check every image required by the selected host configuration;
- report missing image names literally;
- stop before starting services when an image is missing.

### Integrity and architecture

The portable bundle must record its CPU architecture, such as `amd64`. Images prepared for one architecture are not assumed to run on another architecture.

`SHA256SUMS` must cover the archive and manifests so that damaged or incomplete transfers can be detected before import.

## Responsibilities of each infrastructure directory

- `ALINA_BE` retains Docker Compose definitions, PHP Dockerfiles, and host-aware administration commands.
- `ALINA_FE` retains frontend source and built frontend assets. It currently introduces no separate runtime Docker image.
- `ALINA_V` retains video and signaling source code.
- `STORAGE/DOCKER` retains portable Docker image archives, manifests, verification tools, import tools, and their documentation.

## Data not contained in Docker image archives

The Docker image bundle contains software images. It does not automatically contain bind-mounted or host-specific data such as:

- MySQL database data or database dumps;
- `_GITOUT/env/.env`;
- TLS certificates;
- uploaded user files;
- logs;
- host-specific configuration;
- source repositories and their uncommitted changes.

Those items must continue to be transferred or restored through the appropriate existing ALINA storage, backup, and host-configuration procedures.

## Future implementation checklist

- Select and document exact stable names for custom images.
- Pin the Certbot image version.
- Create the prepared signaling image.
- Remove runtime `npm install` from the signaling service.
- Add explicit image names to Compose build services.
- Implement image inventory generation.
- Implement archive export and compression.
- Implement checksum generation and verification.
- Implement image import.
- Implement selected-host image validation.
- Enforce `--no-build` and `--pull never` for offline startup.
- Test on a destination machine with network access disabled.
- Document update and rollback procedures for image bundles.
