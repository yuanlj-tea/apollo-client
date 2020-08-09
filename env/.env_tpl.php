<?php
echo "
APP_NAME=Laravel
APP_ENV=local
APP_KEY=U2o113Ua21fhPYBhHTr5bgVfqritVuk0
APP_DEBUG={$apollo['app_debug']}
APP_URL={$apollo['app_url']}

LOG_CHANNEL=stack

DB_CONNECTION=mysql
DB_HOST={$apollo['mysql.host']}
DB_PORT={$apollo['mysql.port']}
DB_DATABASE={$apollo['mysql.database']}
DB_USERNAME={$apollo['mysql.user']}
DB_PASSWORD={$apollo['mysql.password']}

BROADCAST_DRIVER=log
CACHE_DRIVER=redis
CACHE_PREFIX=laravel
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME={$apollo['session_lifetime']}

SESSION_COOKIE=oa_session
SESSION_CONNECTION=session
SESSION_DOMAIN=


REDIS_HOST={$apollo['redis.host']}
REDIS_PASSWORD={$apollo['redis.password']}
REDIS_PORT={$apollo['redis.port']}
REDIS_PREFIX=

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=

PUSHER_APP_ID=
PUSHER_APP_KEY=
PUSHER_APP_SECRET=
PUSHER_APP_CLUSTER=mt1

MIX_PUSHER_APP_KEY=
MIX_PUSHER_APP_CLUSTER=

OA_SITE = http://192.168.90.206

UPLOAD_FILE_PATH={$apollo['upload_file_path']}
#测试环境 env文件 OBS配置
OBS_APP_ID = {$apollo['obs_app_id']}
OBS_APP_KEY = {$apollo['obs_app_key']}
OBS_API_URL = {$apollo['obs_api_url']}
OBS_BUCKET_NAME = {$apollo['obs_bucket_name']}
OBS_TOP_LEVEL_DIRECTORY = {$apollo['obs_top_level_directory']}
OBS_ENDPOINT = {$apollo['obs_endpoint']}
OBS_HOST =  {$apollo['obs_host']}

";
