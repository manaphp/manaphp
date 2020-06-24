FROM manaphp/php74:3.1

RUN  ln -f -s /usr/share/zoneinfo/PRC /etc/localtime
COPY . /var/www/html

CMD ["php", "/var/www/html/public/index.php"]
