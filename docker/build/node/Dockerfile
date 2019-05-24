FROM node:9-alpine

ARG USER_ID
ARG GROUP_ID

EXPOSE 3000

RUN set -ex; \
    \
    apk add --update \
        bash \
        ca-certificates \
        curl \
        git \
        make \
        wget \
        shadow \
    sudo; \
    rm -rf /var/cache/apk/*

# Setup user.
# ---------------------------------------------------------------------------------------------------------------
RUN if [ -z "`getent group ${GROUP_ID}`" ]; then \
      addgroup -S -g ${GROUP_ID} www-data; \
    else \
      groupmod -n www-data `getent group ${GROUP_ID} | cut -d: -f1`; \
    fi && \
    if [ -z "`getent passwd ${USER_ID}`" ]; then \
      adduser -S -u $UID -G www-data -s /bin/sh www-data; \
    else \
      usermod -l www-data -g ${GROUP_ID} -d /home/www-data -m `getent passwd ${USER_ID} | cut -d: -f1`; \
    fi && \
    echo "www-data ALL=(root) NOPASSWD:ALL" > /etc/sudoers.d/www-data && \
    chmod 0440 /etc/sudoers.d/www-data


# Setup NPM.
# ---------------------------------------------------------------------------------------------------------------
RUN npm config set -g proxy ${HTTP_PROXY}
RUN npm config set -g https-proxy ${HTTPS_PROXY}