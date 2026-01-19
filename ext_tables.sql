CREATE TABLE sys_file_metadata (
    pixxio_file_id int(11) NOT NULL DEFAULT '0',
    pixxio_mediaspace varchar(255) NOT NULL DEFAULT '',
    pixxio_downloadformat varchar(255) NOT NULL DEFAULT '',
    pixxio_last_sync_stamp int(11) NOT NULL DEFAULT '0',
    pixxio_custom_metadata text
);
