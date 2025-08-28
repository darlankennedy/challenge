FROM ubuntu/apache2:2.4-20.04_edge

WORKDIR /backend/

RUN apt-get update && apt-get install -y vim
RUN a2enmod ssl rewrite headers mpm_event http2 actions proxy proxy_fcgi setenvif info
