FROM ubuntu:latest
ENV DEBIAN_FRONTEND=noninteractive TZ=America/New_York APACHE_RUN_USER=www-data APACHE_RUN_GROUP=www-data APACHE_LOG_DIR=/var/log/apache2 APACHE_LOCK_DIR=/var/lock/apache2 APACHE_PID_FILE=/var/run/apache2.pid
COPY . /src
WORKDIR /src
RUN apt-get update && \
  apt-get -qq -y install apt-utils cron tzdata python python-pip python-tk libmysqlclient-dev apache2 php libapache2-mod-php php-mysql curl && \
  ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone && \
  rm -rf /var/www/html && ln -snf /src/html /var/www/html && \
  pip install -r requirements.txt && \
  crontab /src/cron/crontab && rm /src/cron/crontab && \
  service apache2 start
# start webserver
EXPOSE 80
CMD cron -f
