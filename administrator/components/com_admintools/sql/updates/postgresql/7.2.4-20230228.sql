/**
 * @package   admintools
 * @copyright Copyright (c)2010-2026 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

-- DO NOT REMOVE THE "USELESS" EMPTY COMMENT.
-- Joomla! misdetects the table name as "#__admintools_ipallow(" if it's missing.

CREATE TABLE IF NOT EXISTS "#__admintools_ipallow" /**/
(
    "id"          serial NOT NULL,
    "ip"          character varying(255) DEFAULT NULL,
    "description" character varying(255) DEFAULT NULL,
    PRIMARY KEY ("id"),
    CONSTRAINT "#__admintools_ipallow_ip" UNIQUE ("ip")
);
