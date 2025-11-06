FROM ubuntu:22.04

RUN apt-get update && apt-get install -y cron mariadb-client bash

COPY backup.sh /backup.sh
COPY crontab.txt /etc/cron.d/backup-cron

RUN chmod 0644 /etc/cron.d/backup-cron && chmod +x /backup.sh

RUN crontab /etc/cron.d/backup-cron

CMD ["cron", "-f"]
