CREATE TABLE sys_file_metadata (
    pixxio_file_id int(11) NOT NULL DEFAULT '0',
    pixxio_mediaspace varchar(255) NOT NULL DEFAULT '',
    pixxio_downloadformat varchar(255) NOT NULL DEFAULT '',
    pixxio_last_sync_stamp int(11) NOT NULL DEFAULT '0',
    pixxio_is_direct_link int(1) NOT NULL DEFAULT '0',
    pixxio_direct_link varchar(255) NOT NULL DEFAULT '',
    tx_pixxioextension_licensereleases text
);

CREATE TABLE tx_pixxioextension_domain_model_licenserelease (
    uid int(11) NOT NULL auto_increment,
    pid int(11) DEFAULT '0' NOT NULL,
    sys_file_metadata int(1) NOT NULL DEFAULT '0',
    license_provider varchar(255) DEFAULT '' NOT NULL COMMENT 'License Provider',
    name varchar(255) DEFAULT '' NOT NULL COMMENT 'Licence Name',
    show_warning_message tinyint(1) DEFAULT '0' NOT NULL COMMENT 'Show Warning Message',
    warning_message text COMMENT 'Warning Message',
    expires varchar(255) DEFAULT '' NOT NULL COMMENT 'Expires',
    PRIMARY KEY (uid),
    KEY parent (pid)
);
