FROM ubuntu:latest
ENV DEBIAN_FRONTEND=noninteractive
ENV APACHE_RUN_USER=www-data APACHE_RUN_GROUP=www-data APACHE_LOG_DIR=/var/log/apache2 APACHE_LOCK_DIR=/var/lock/apache2 APACHE_PID_FILE=/var/run/apache2.pid
ENV TZ=America/New_York
#COPY . /src # instead mount when doing `docker run`
COPY requirements.txt /src/requirements.txt
WORKDIR /src
RUN apt-get update && \
  apt-get -qq -y install apt-utils tzdata python python-pip python-tk libmysqlclient-dev apache2 php libapache2-mod-php php-mcrypt php-mysql curl && \
  ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone && \
  rm -rf /var/www/html && ln -snf /src/html /var/www/html && \
  pip install -r requirements.txt && \
# start webserver
EXPOSE 80
#CMD ["python", "test.py"]
CMD /usr/sbin/apache2ctl -D FOREGROUND
