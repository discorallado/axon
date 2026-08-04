
#ddev-generated - Do not modify this file; your modifications will be overwritten.
ARG BASE_IMAGE="scratch"

### DDEV-injected base Dockerfile contents
FROM $BASE_IMAGE
SHELL ["/bin/bash", "-c"]

ARG TARGETPLATFORM
ARG TARGETARCH
ARG TARGETOS
ARG username
ARG uid
ARG gid
ARG DDEV_PHP_VERSION
ARG DDEV_DATABASE
RUN getent group tty || groupadd tty
RUN (groupadd --gid "$gid" "$username" || groupadd "$username" || true) && \
    (useradd -G tty -l -m -s "/bin/bash" --gid "$username" --comment '' --uid "$uid" "$username" || \
    useradd -G tty -l -m -s "/bin/bash" --gid "$username" --comment '' "$username" || \
    useradd -G tty -l -m -s "/bin/bash" --gid "$gid" --comment '' "$username" || \
    useradd -G tty -l -m -s "/bin/bash" --comment '' "$username")

### DDEV-injected extra content

RUN mkdir -p /home/$username && chown $username /home/$username && chmod 600 /home/$username/.pgpass
### DDEV-injected Node.js install via n
RUN <<EOF
set -eu -o pipefail
cd /tmp/n-version-files || true
export N_PREFIX=/usr/local/n
n install --cleanup "22" || log-stderr.sh n install --cleanup "22" || true
NODE_VERSION=$(node -v)
IFS=. read -r NODE_MAJOR NODE_MINOR _ <<< "${NODE_VERSION#v}"
INSTALL_PKGS=""
if [ "$NODE_MAJOR" -lt 10 ] || { [ "$NODE_MAJOR" -eq 10 ] && [ "$NODE_MINOR" -lt 13 ]; }; then
  INSTALL_PKGS="$INSTALL_PKGS gulp-cli@2" # for Node.js <10.13
fi
if [ "$NODE_MAJOR" -lt 6 ]; then
  INSTALL_PKGS="$INSTALL_PKGS yarn@1.11.1" # for Node.js <6
fi
if [ -n "$INSTALL_PKGS" ]; then
  log-stderr.sh npm install --unsafe-perm=true --global $INSTALL_PKGS -f || true
fi
cd /tmp && rm -rf /tmp/n-version-files
EOF

RUN log-stderr.sh mariadb-compat-install.sh || true

RUN log-stderr.sh mariadb-skip-ssl-wrapper-install.sh || true


### DDEV-injected composer update
RUN export XDEBUG_MODE=off; composer self-update --stable || composer self-update --stable || true; composer self-update --2 || log-stderr.sh composer self-update --2 || true

### DDEV-injected folders permission fix
RUN mkdir -p /run/php /var/run/nginx /var/run/supervisor /var/run/apache2 /var/lock/apache2 && chown "$uid:$gid" /run/php /var/run/nginx /var/run/supervisor /var/run/apache2 /var/lock/apache2 && chmod 755 /run/php /var/run/nginx /var/run/supervisor /var/run/apache2 /var/lock/apache2 && if [ -d /run/blackfire ]; then chown "$uid:$gid" /run/blackfire && chmod 755 /run/blackfire; fi && \
    mkdir -p /var/log/nginx /var/log/apache2 && \
    touch /var/log/nginx/error.log /var/log/nginx/access.log /var/log/php-fpm.log /var/log/supervisord.log && \
    chmod ugo+rw /var/log/nginx/error.log /var/log/nginx/access.log /var/log/php-fpm.log /var/log/supervisord.log && \
    chmod ugo+rwX /var/log/nginx /var/log/apache2 && \
    chgrp -R "$gid" /etc/alternatives /var/lib/dpkg/alternatives /usr/local/n && \
    chmod -R g+rwX,o-w /etc/alternatives /var/lib/dpkg/alternatives /usr/local/n && \
    chmod -f ugo+rx /usr/local/bin /usr/local/bin/* && \
    chgrp "$gid" /usr/local/bin/composer && chmod g+rwx,o-w /usr/local/bin/composer && \
    mkdir -p /tmp/xhprof && chmod -R ugo+w /etc/php /var/lib/php /tmp/xhprof

### DDEV-injected removal of the ddev-keep-root shim (only needed for apache-fpm on Docker rootless)
RUN rm -f /usr/local/lib/ddev/ddev-keep-root.so
