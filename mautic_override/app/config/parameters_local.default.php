<?php

/**
 * @file - `parameters_local.default.php` is to be copied to `parameters_local.php` in this same directory.
 * @description - $parameters_local.php contains all of the Mautic configuration code to
 *                override the current database configuration.
 */

$parameters = [
	'install_source' => 'Mautic',
	'theme' => 'Mauve',
	'locale' => 'en_US',
    // @TODO - Add rememberme_key via GitLab CI variable
	'rememberme_key' => '',
	'rememberme_lifetime' => '31536000',
	'rememberme_path' => '/',
	'cookie_path' => '/',
	'cookie_secure' => '1',
	'cookie_httponly' => '0',
	'redirect_list_types' => array(
		"301" => "mautic.page.form.redirecttype.permanent", 
		"302" => "mautic.page.form.redirecttype.temporary"
	),
    // Enable the API
	'api_enabled' => 1,
	'api_oauth2_access_token_lifetime' => 60,
	'api_oauth2_refresh_token_lifetime' => 14,
	'active' => 1,
	'utm_source' => 'mautic',
	'utm_medium' => 'email',
	'utm_campaign' => 'subject',
	'remove_accents' => 1,
	'do_not_track_internal_ips' => array(),
	'cached_data_timeout' => '10',
	'batch_sleep_time' => '1',
	'campaign_time_wait_on_event_false' => 'PT1H',
	'notification_enabled' => 0,
	'notification_app_id' => null,
	'notification_rest_api_key' => null,
	'notification_safari_web_id' => null,
	'twitter_handle_field' => 'twitter',
	'rss_notification_url' => 'https://mautic.com/?feed=rss2&tag=notification',
	'api_enable_basic_auth' => true,
	'gcm_sender_id' => '482941778795',
	'welcomenotification_enabled' => 1,
	'do_not_track_bots' => array(
		"0" => "MSNBOT", 
		"1" => "msnbot-media", 
		"2" => "bingbot", 
		"3" => "Googlebot", 
		"4" => "Google Web Preview", 
		"5" => "Mediapartners-Google", 
		"6" => "Baiduspider", 
		"7" => "Ezooms", 
		"8" => "YahooSeeker", 
		"9" => "Slurp", 
		"10" => "AltaVista", 
		"11" => "AVSearch", 
		"12" => "Mercator", 
		"13" => "Scooter", 
		"14" => "InfoSeek", 
		"15" => "Ultraseek", 
		"16" => "Lycos", 
		"17" => "Wget", 
		"18" => "YandexBot", 
		"19" => "Java/1.4.1_04", 
		"20" => "SiteBot", 
		"21" => "Exabot", 
		"22" => "AhrefsBot", 
		"23" => "MJ12bot", 
		"24" => "NetSeer crawler", 
		"25" => "TurnitinBot", 
		"26" => "magpie-crawler", 
		"27" => "Nutch Crawler", 
		"28" => "CMS Crawler", 
		"29" => "rogerbot", 
		"30" => "Domnutch", 
		"31" => "ssearch_bot", 
		"32" => "XoviBot", 
		"33" => "digincore", 
		"34" => "fr-crawler", 
		"35" => "SeznamBot", 
		"36" => "Seznam screenshot-generator", 
		"37" => "Facebot", 
		"38" => "facebookexternalhit"
	),
	'api_batch_max_limit' => '200',
	'notification_landing_page_enabled' => 1,
	'max_entity_lock_time' => 0,
	'parallel_import_limit' => '1',
	'background_import_if_more_rows_than' => 0,
	'events_orderby_dir' => 'ASC',
];

/**
 * Database Credentials
 *
 */
$parameters += [
    'db_driver' => 'pdo_mysql',
    'db_host' => getenv('MYSQL_DB_HOST'),
    'db_port' => '3306',
    'db_name' => getenv('MYSQL_DATABASE'),
    'db_user' => getenv('MYSQL_USER'),
    'db_password' => getenv('MYSQL_PASSWORD'),
    'db_path' => null,
    'db_table_prefix' => null,
    'db_backup_tables' => 0,
    'db_backup_prefix' => 'bak_',
    // @TODO - db_server_version should be dynamic.
    'db_server_version' => '5.7'
];

/**
 * System Settings - General Settings Configuration
 */
$parameters += [
    // Host Settings
    'site_url' => getenv('SITE_URL'),
    'secret_key' => getenv('SECRET_KEY'),
    'webroot' => null,
    // Mautic Update Channel
    'update_stability' => 'stable',

    // Path Settings
    'cache_path' => '/cache',
    'log_path' => '/logs',
    'image_path' => 'media/images',
    'tmp_path' => '/cache',

    // System Defaults
    'default_pagelimit' => 30,
    'default_timezone' => 'UTC',
    'date_format_full' => 'F j, Y g:i a T',
    'date_format_short' => 'D, M d',
    'date_format_dateonly' => 'F j, Y',
    'date_format_timeonly' => 'g:i a',

];

/**
 * CORS Settings
 */
// Restrict domains via CORS
$parameters['cors_restrict_domains'] = 1;
// Specify CORS valid domains
$parameters['cors_valid_domains'] = array_filter(explode(",",getenv('CORS_DOMAINS')));

/**
 * Dev Settings
 */
if (getenv('LANDO_DOMAIN') == 'lndo.site') {
    /**
     * Dev Hosts
     *
     * @description: Set this if you want to enable Mautic's Dev Mode via external hosts
     * @TODO - Make this work dynamically with Lando Site name
     */
    $parameters['dev_hosts'] = [
        'appserver_nginx.mautic.internal',
        'mautic.lndo.site',
        '127.0.0.1'
    ];
} else {
    $parameters['dev_hosts'] = null;
}

/**
 * Miscellaneous Settings
 */

// Trusted Hosts
$parameters['trusted_hosts'] = [];

// Trusted Proxies
// Required for Load Balanced Application Containers Behind a Proxy in Mautic
$parameters['trusted_proxies'] = array_filter(explode(",",getenv('TRUSTED_PROXIES')));

// Do Not Track IPs
$parameters['do_not_track_ips'] = array_filter(explode(",", getenv('DONOTTRACK_IPS')));

// IP Lookup Service
$parameters += [
    'ip_lookup_service' => 'maxmind_download',
    'ip_lookup_auth' => null,
    'ip_lookup_config' => array(),
];

// URL Shortener
$parameters['link_shortener_url'] = null;

/**
 * Asset Settings
 */
$parameters += [
    'upload_dir' => '%kernel.root_dir%/media/files',
    'max_size' => '6',
    'allowed_extensions' => array(
        "0" => "csv",
        "1" => "doc",
        "2" => "docx",
        "3" => "epub",
        "4" => "gif",
        "5" => "jpg",
        "6" => "jpeg",
        "7" => "mpg",
        "8" => "mpeg",
        "9" => "mp3",
        "10" => "odt",
        "11" => "odp",
        "12" => "ods",
        "13" => "pdf",
        "14" => "png",
        "15" => "ppt",
        "16" => "pptx",
        "17" => "tif",
        "18" => "tiff",
        "19" => "txt",
        "20" => "xls",
        "21" => "xlsx",
        "22" => "wav"
    ),
];

/**
 * Email Settings
 */
$parameters += [
    // Mail Send Settings
    'mailer_from_name' => 'My Mautic',
    // From Email Address
    'mailer_from_email' => 'mautic@mydomain.com',
    // SELECT Mailer Transport
    'mailer_transport' => 'mautic.transport.amazon',
    // Send email from Lead Owner
    'mailer_is_owner' => 1,

    // Mailer Connection Credentials
    'mailer_host' => getenv('MAILER_HOST'),
    'mailer_port' => '587',
    'mailer_user' => getenv('MAILER_USER'),
    'mailer_password' => getenv('MAILER_PASSWORD'),
    'mailer_api_key' => null,

    // AWS SES
    // For Amazon the Mailer_Host matches this variable
    'mailer_amazon_region' => getenv('MAILER_HOST'),

    // MailJet
    'mailer_mailjet_sandbox' => 0,
    'mailer_mailjet_sandbox_default_mail' => null,

    // Other Mail Settings
    'mailer_encryption' => 'tls',
    'mailer_auth_mode' => 'login',
    'mailer_spool_type' => 'memory',
    'mailer_spool_path' => '/mnt/spool',
    'mailer_return_path' => null,
    'mailer_spool_msg_limit' => null,
    'mailer_spool_time_limit' => null,
    'mailer_spool_recover_timeout' => '900',
    'mailer_spool_clear_timeout' => '1800',

    // Default Frequency Rules
    'email_frequency_number' => 1,
    'email_frequency_time' => 'DAY',

    // Message Settings;
    // also see: Tracking Configuration
    'webview_text' => '<a href=\'|URL|\'>Trouble reading this email? Click here.</a>',
    // @TODO: Convert to values.yaml file
    'default_signature_text' => 'My Signature

C: 123-456-7890',

    // Message Settings: Email Tracking
    'mailer_append_tracking_pixel' => 1,
    'mailer_convert_embed_images' => 0,
    'disable_trackable_urls' => 0,

    // Unsubscribe Settings
    'unsubscribe_text' => '<a href=\'|URL|\'>Unsubscribe</a> to no longer receive emails from us.',
    'unsubscribe_message' => 'We are sorry to see you go! |EMAIL| will no longer receive emails from us. If this was by mistake, <a href=\'|URL|\'>click here to re-subscribe</a>.',
    'resubscribe_message' => '|EMAIL| has been re-subscribed. If this was by mistake, <a href=\'|URL|\'>click here to unsubscribe</a>.',
    'show_contact_preferences' => 1,
    'show_contact_frequency' => 0,
    'show_contact_pause_dates' => 0,
    'show_contact_preferred_channels' => 0,
    'show_contact_categories' => 1,
    'show_contact_segments' => 0,

    // Monitored Inbox Settings
    'monitored_email' => array(
        "general" => array(
            "address" => "",
            "host" => "",
            "port" => "993",
            "encryption" => "/ssl",
            "user" => "",
            "password" => ""
        ),
        "EmailBundle_bounces" => array(
            "address" => "",
            "host" => "",
            "port" => "993",
            "encryption" => "/ssl",
            "user" => "",
            "password" => "",
            "override_settings" => "0",
            "folder" => "",
            "ssl" => "1"
        ),
        "EmailBundle_unsubscribes" => array(
            "address" => "",
            "host" => "",
            "port" => "993",
            "encryption" => "/ssl",
            "user" => "",
            "password" => "",
            "override_settings" => "0",
            "folder" => "",
            "ssl" => "1"
        ),
        "EmailBundle_replies" => array(
            "address" => "",
            "host" => "",
            "port" => "993",
            "encryption" => "/ssl",
            "user" => "",
            "password" => "",
            "override_settings" => "0",
            "folder" => ""
        )
    ),
];

/**
 * Queue System: Mail and SMS
 */
$parameters += [
    'queue_mode' => 'queue', // queue, immediate
];

/**
 * Queue System: RabbitMQ
 */
$parameters += [
    'queue_protocol' => 'rabbitmq',
    'rabbitmq_host' => getenv('RABBITMQ_HOST'),
    'rabbitmq_port' => '5672',
    'rabbitmq_vhost' => '/',
    'rabbitmq_user' => getenv('RABBITMQ_USER'),
    'rabbitmq_password' => getenv('RABBITMQ_PASSWORD'),
];

/**
 * Queue System: Beanstalk
 *
 * Cannot be enabled with RabbitMQ block above
 */
/*
 $parameters += [
    'queue_protocol' => 'beanstalkd',
    'beanstalkd_host' => 'localhost',
    'beanstalkd_port' => '11300',
    'beanstalkd_timeout' => '60',
];
 */

/**
 * Tracking Configuration
 *
 * @TODO - Convert domains to an exploded array
 */
$parameters += [
    'track_by_fingerprint' => 0,
    'track_by_tracking_url' => 1,
    // @TODO - Investigate: I think this is actually: Anonymize IP
    'track_contact_by_ip' => 0,
    'google_analytics_id' => null,
    'google_analytics_trackingpage_enabled' => 0,
    'google_analytics_landingpage_enabled' => 0,
    'facebook_pixel_id' => null,
    'facebook_pixel_trackingpage_enabled' => 0,
    'facebook_pixel_landingpage_enabled' => 0,
];

/**
 * Landing Page Settings
 *
 * @TODO - Convert into variable.
 */
$parameters += [
    'cat_in_page_url' => 0,
    'google_analytics' => '&lt;script&gt;
  (function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,\'script\',\'https://www.google-analytics.com/analytics.js\',\'ga\');
​
  ga(\'create\', \'' . getenv('GA_TRACKING_ID') . '\', \'auto\');
  ga(\'send\', \'pageview\');
​
&lt;/script&gt;',
];

/**
 * Text Message Settings (SMS)
 */
$parameters += [
    // Install SMS Transport via Plugin
    'sms_transport' => null,
    'sms_enabled' => 0,
    'sms_username' => null,
    'sms_password' => null,
    'sms_sending_phone_number' => null,
    'sms_frequency_number' => null,
    'sms_frequency_time' => null,
];

/**
 * User / Authentication Settings
 */
$parameters += [
    'saml_idp_entity_id' => getenv('SAML_IDP_IDENTITY_ID'),
    'saml_idp_own_password' => null,
    'saml_idp_email_attribute' => 'EmailAddress',
    'saml_idp_username_attribute' => null,
    'saml_idp_firstname_attribute' => 'FirstName',
    'saml_idp_lastname_attribute' => 'LastName',
    'saml_idp_default_role' => 1,
];

/**
 * Webhook Settings
 */
$parameters += [
    'webhook_start' => '0',
    'webhook_limit' => '1000',
    'webhook_log_max' => '10',
    'webhook_disable_limit' => '100',
    'webhook_timeout' => '15',
];

/**
 * Error Level Settings
 *
 * @TODO - Change Helm to expose NAMESPACE so we can do an exact match
 */
if (strpos(getenv('HOSTNAME'),'mautic-master-mautic') !== false ) {
    // Turn on All Error Reporting
    error_reporting(E_ALL && ~E_NOTICE && ~E_WARNING);
    ini_set('display_errors', FALSE);
    ini_set('display_startup_errors', FALSE);
    $parameters += [
        'debug' => false,
    ];
} else {
    // Turn on All Error Reporting
    error_reporting(E_ALL);
    ini_set('display_errors', TRUE);
    ini_set('display_startup_errors', TRUE);
    $parameters += [
        'debug' => true,
    ];
}
