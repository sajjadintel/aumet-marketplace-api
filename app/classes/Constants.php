<?php

class Constants
{
    ####################### GENERIC START
    ## HTTP Responses
    const HTTP_OK = 200;
    const HTTP_BAD_REQUEST = 400;
    const HTTP_UNAUTHORIZED = 401;
    const HTTP_FORBIDDEN = 403;
    const HTTP_NOT_FOUND = 404;
    const HTTP_UNSUPPORTED_MEDIA_TYPE = 415;

    ## ENV TYPES
    const ENV_LOCAL = "loc";
    const ENV_DEV = "dev";
    const ENV_BETA = "beta";
    const ENV_PROD = "prod";

    ## API Request Log
    const LOG_TYPE_INIT = 1;
    const LOG_TYPE_SUCCESS = 2;
    const LOG_TYPE_ERROR = 3;
    ####################### GENERIC END

    ## Mailing 
    const APP_MAIN_EMAIL = "bot@aumet.tech";
    const APP_MAIN_EMAIL_FROM_NAME = "Aumet Bot";
    const MAILING_LIST_PROD = [
        "patrick.younes.1.py@gmail.com"
    ];

    const MAILING_LIST_DEV = [
        "patrick.younes.1.py@gmail.com"
    ];

    const MAILING_LIST_LOC = [
        "patrick.younes.1.py@gmail.com"
    ];

    ## User States
    const USER_STATE_SIGNED_UP = 1;
    const USER_STATE_VERIFIED = 2;
    const USER_STATE_APPROVED = 3;

    ## Verification Token
    const VERIFICATION_TOKEN_CREATED = 1;
    const VERIFICATION_TOKEN_ACTIVATED = 2;
    const VERIFICATION_TOKEN_EXPIRED = 3;
    const VERIFICATION_TOKEN_LIFETIME = 1; //in hours

    // User verification code token status
    const USER_VERIFICATION_TOKEN_PENDING = 1;
    const USER_VERIFICATION_TOKEN_GENERATED = 2;
    const USER_VERIFICATION_TOKEN_USED = 3;
    const USER_VERIFICATION_TOKEN_CANCELLED = 4;

    ## Notification Entities
    const NOTIFICATION_ENTITY_ALL = 1;

    ## Notification Read Status
    const NOTIFICATION_STATUS_UNREAD = 1;
    const NOTIFICATION_STATUS_READ = 2;

    ## Notification Type
    const NOTIFICATION_TYPE_ALL = 1;
}
