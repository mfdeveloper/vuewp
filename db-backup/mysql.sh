#!/bin/sh

BASEDIR=$(dirname "$0")

# REFERENCE: https://gist.github.com/spalladino/6d981f7b33f6e0afe6bb
dumpSql () {
    if [ -z "$1" ]
    then
        echo "A docker CONTAINER name that running mysql is required!!"
    else
        echo "Generating .sql backup from CONTAINER: $1"
        docker exec -it $1 sh -c 'exec mysqldump --all-databases -uroot -p"$MYSQL_ROOT_PASSWORD"' > ${BASEDIR}/mysql.databases.sql
    fi
}

# REFERENCE: https://loomchild.net/2017/03/26/backup-restore-docker-named-volumes/
# REFERENCE 2: https://gist.github.com/spalladino/6d981f7b33f6e0afe6bb
dumpFolder () {
    if [ -z "$1" ]
    then
        echo "A docker CONTAINER name that running mysql is required!!"
    else
        echo "Generating .tar.gz backup of: /var/lib/mysql from CONTAINER: $1"
        docker exec -it $1 sh -c 'tar -czvf /backup/mysql.databases.tar.gz /var/lib/mysql'
    fi
}

$*