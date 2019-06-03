/***************************************************************************************************
 * ADDING NEW VERSION TO SCHEMA *******************************************************************/
SET @schemaKey = 'reveal.banner.table';
SET @schemaKeyVersion = 1;

INSERT INTO `_schema_version` (`key`, `key_version`, `start_time`) VALUES (@schemaKey, @schemaKeyVersion, NOW());
-- -------------------------------------------------------------------------------------------------

DROP TABLE  reveal_banner;

CREATE TABLE reveal_banner
(
  id                INT          NOT NULL AUTO_INCREMENT,
  enabled           TINYINT      NOT NULL,
  before_timer_text VARCHAR(250) NOT NULL,
  before_timer_link VARCHAR(250) NOT NULL,
  timer_target      DATETIME     NOT NULL,
  after_timer_text  VARCHAR(250) NOT NULL,
  after_timer_link  VARCHAR(250) NOT NULL,
  date_start_show   DATETIME     NOT NULL,
  date_end_show     DATETIME     NOT NULL,
  PRIMARY KEY (`id`)
)
  ENGINE = InnoDB
  DEFAULT CHARSET = utf8
  COLLATE = utf8_unicode_ci;

INSERT INTO reveal_banner (id, enabled, before_timer_text, before_timer_link, timer_target, after_timer_text, after_timer_link, date_start_show, date_end_show)
VALUES (1, 1, 'And the next Princess is...', '/disney-princess-reveal', '2017-09-05 09:00:00',
        'See who September''s Disney Princess PleyBox will feature', '/disney-princess-revealed', '2017-09-01 00:00:00',
        '2017-10-30 00:00:00');
/***************************************************************************************************
 * UPDATING VERSIONING SCHEMA THAT WE FINISHED  INITIALIZING THE BASE LINE VERSION ****************/
UPDATE `_schema_version`
SET `end_time` = NOW()
WHERE `key` = @schemaKey COLLATE utf8_general_ci AND `key_version` = @schemaKeyVersion;
-- -------------------------------------------------------------------------------------------------