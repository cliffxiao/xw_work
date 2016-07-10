#!/bin/bash 
DBHost=127.0.0.1
DBName=chinacarbon_new
DBUser=root 
DBPasswd=343221
DBPort=3306

BackupPath=/data/www/db_bak/chinacarbon/
LogFile=/data/www/db_bak/chinacarbon/db.log
BackupMethod=mysqldump

NewFile="$BackupPath"db$(date +%y%m%d).tgz 
DumpFile="$BackupPath"db$(date +%y%m%d)
DumpFileTable="$BackupPath"dbtable$(date +%y%m%d)
OldFile="$BackupPath"db$(date +%y%m%d --date='7 days ago').tgz

#Delete Old File 
if [ -f $OldFile ] 
then 
   rm -f $OldFile >> $LogFile 2>&1 
fi 
if [ -f $NewFile ] 
then 
   echo "[$NewFile]The Backup File is exists,Can't Backup!" >> $LogFile 
else 
   case $BackupMethod in 
   mysqldump) 
      if [ -z $DBPasswd ] 
      then
         mysqldump -h$DBHost -u $DBUser -P$DBPort -d $DBName --lock-tables=false --ignore-table=ehsy_new.ehsy_product_content --ignore-table=ehsy_new.view_product_attr --ignore-table=ehsy.view_item --ignore-table=mdsin.mdsin_product_content>> $DumpFileTable 
         mysqldump -h$DBHost -u $DBUser -P$DBPort -t $DBName --lock-tables=false --ignore-table=ehsy_new.ehsy_product_content --ignore-table=ehsy_new.view_product_attr --ignore-table=ehsy.view_item --ignore-table=mdsin.mdsin_product_content>> $DumpFile 
      else
         mysqldump -h$DBHost -u $DBUser -p$DBPasswd -P$DBPort -d $DBName --lock-tables=false --ignore-table=ehsy_new.ehsy_product_content --ignore-table=ehsy_new.view_product_attr --ignore-table=ehsy.view_item --ignore-table=mdsin.mdsin_product_content>> $DumpFileTable 
         mysqldump -h$DBHost -u $DBUser -p$DBPasswd -P$DBPort -t $DBName --lock-tables=false --ignore-table=ehsy_new.ehsy_product_content --ignore-table=ehsy_new.view_product_attr --ignore-table=ehsy.view_item --ignore-table=mdsin.mdsin_product_content>> $DumpFile 
      fi 
      tar czvf $NewFile $DumpFile $DumpFileTable >> $LogFile 2>&1
      echo "[$(date +"%y-%m-%d %H:%M:%S") $NewFile]Backup Success!" >> $LogFile 
      rm -rf $DumpFile
      rm -rf $DumpFileTable 
      ;; 
   esac 
fi
