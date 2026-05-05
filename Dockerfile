FROM php:8.2-apache

RUN apt-get update && \
    apt-get install -y docker.io dmidecode curl && \
    rm -rf /var/lib/apt/lists/*

RUN groupmod -g 137 docker || true
RUN usermod -aG docker www-data
