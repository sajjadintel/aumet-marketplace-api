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

    ### User status
    const USER_STATUS_WAITING_VERIFICATION = 1;
    const USER_STATUS_PENDING_APPROVAL = 2;
    const USER_STATUS_ACCOUNT_ACTIVE = 3;

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

    ## Bonus type
    const BONUS_TYPE_FIXED = 1;
    const BONUS_TYPE_PERCENTAGE = 2;
    const BONUS_TYPE_DYNAMIC = 3;

    ## Order status
    const ORDER_STATUS_PENDING = 1;
    const ORDER_STATUS_ONHOLD = 2;
    const ORDER_STATUS_PROCESSING = 3;
    const ORDER_STATUS_COMPLETED = 4;
    const ORDER_STATUS_CANCELED = 5;
    const ORDER_STATUS_RECEIVED = 6;
    const ORDER_STATUS_PAID = 7;
    const ORDER_STATUS_MISSING_PRODUCTS = 8;
    const ORDER_STATUS_CANCELED_PHARMACY = 9;

    ## Email Types
    const EMAIL_ORDER_UPDATE = 'Order Update';
    const EMAIL_CHANGE_PROFILE_APPROVAL = 'Change Profile Approval';
    const EMAIL_NEW_ORDER = 'New Order';
    const EMAIL_LOW_STOCK = 'Low Stock';
    const EMAIL_RESET_PASSWORD = 'Reset Password';
    const EMAIL_MISSING_PRODUCTS = 'Missing Products';
    const EMAIL_MODIFY_SHIPPED_QUANTITY = 'Modify Shipped Quantity';
    const EMAIL_CUSTOMER_SUPPORT_REQUEST = 'Customer Support Request';
    const EMAIL_CUSTOMER_SUPPORT_CONFIRMATION = 'Customer Support Confirmation';
    const EMAIL_ORDER_STATUS_UPDATE = 'Order Status Update';
    const EMAIL_PHARMACY_ACCOUNT_VERIFICATION = 'Pharmacy Account Verification';
    const EMAIL_DISTRIBUTOR_ACCOUNT_VERIFICATION = 'Distributor Account Verification';
    const EMAIL_PHARMACY_ACCOUNT_VERIFIED = 'Pharmacy Account Verified';
    const EMAIL_DISTRIBUTOR_ACCOUNT_VERIFIED = 'Distributor Account Verified';
    const EMAIL_PHARMACY_ACCOUNT_APPROVAL = 'Pharmacy Account Approval';
    const EMAIL_DISTRIBUTOR_ACCOUNT_APPROVAL = 'Distributor Account Approval';
    const EMAIL_PHARMACY_ACCOUNT_APPROVED = 'Pharmacy Account Approved';
    const EMAIL_DISTRIBUTOR_ACCOUNT_APPROVED = 'Distributor Account Approved';
    const EMAIL_NEW_CUSTOMER_GROUP = 'New Customer Group';
    const EMAIL_CHANGE_PROFILE_APPROVAL = 'Change Profile Approval';
    const EMAIL_CHANGE_PROFILE_APPROVED = 'Change Profile Approved';



    ### User role
    const USER_ROLE_DISTRIBUTOR_SYSTEM_ADMINISTRATOR = 10;
    const USER_ROLE_DISTRIBUTOR_ENTITY_MANAGER = 20;
    const USER_ROLE_DISTRIBUTOR_SYSTEM_MANAGER = 30;
    const USER_ROLE_PHARMACY_SYSTEM_ADMINISTRATOR = 40;
    const USER_ROLE_AUMET_ADMIN = 1000;

    ### Entity type
    const ENTITY_TYPE_DISTRIBUTOR = 10;
    const ENTITY_TYPE_SUB_DISTRIBUTOR = 11;
    const ENTITY_TYPE_PHARMACY = 20;
    const ENTITY_TYPE_PHARMACY_CHAIN = 21;
    const ENTITY_TYPE_AUMET_ADMIN = 1000;

    ### Account status
    const ACCOUNT_STATUS_ACTIVE = 1;
    const ACCOUNT_STATUS_INACTIVE = 2;
    const ACCOUNT_STATUS_BLOCKED = 3;

}
