--
-- Categories Module SQLite Database for Phire CMS 2.0
--

--  --------------------------------------------------------

--
-- Set database encoding
--

PRAGMA encoding = "UTF-8";
PRAGMA foreign_keys = ON;

-- --------------------------------------------------------

--
-- Table structure for table "categories"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]categories" (
  "id" integer NOT NULL PRIMARY KEY AUTOINCREMENT,
  "parent_id" integer,
  "title" varchar NOT NULL,
  "uri" varchar NOT NULL,
  "slug" varchar,
  "order" integer,
  "filter" integer,
  "hierarchy" varchar,
  UNIQUE ("id"),
  CONSTRAINT "fk_category_parent_id" FOREIGN KEY ("parent_id") REFERENCES "[{prefix}]categories" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

INSERT INTO "sqlite_sequence" ("name", "seq") VALUES ('[{prefix}]categories', 11000);
CREATE INDEX "category_parent_id" ON "[{prefix}]categories" ("parent_id");
CREATE INDEX "category_title" ON "[{prefix}]categories" ("title");
CREATE INDEX "category_uri" ON "[{prefix}]categories" ("uri");
CREATE INDEX "category_slug" ON "[{prefix}]categories" ("slug");
CREATE INDEX "category_order" ON "[{prefix}]categories" ("order");

-- --------------------------------------------------------

--
-- Table structure for table "content_to_categories"
--

CREATE TABLE IF NOT EXISTS "[{prefix}]content_to_categories" (
  "content_id" integer NOT NULL,
  "category_id" integer NOT NULL,
  "type" varchar NOT NULL,
  "order" integer,
  UNIQUE ("content_id", "category_id"),
  CONSTRAINT "fk_content_category_id" FOREIGN KEY ("category_id") REFERENCES "[{prefix}]categories" ("id") ON DELETE CASCADE ON UPDATE CASCADE
) ;

CREATE INDEX "category_content_id" ON "[{prefix}]content_to_categories" ("content_id");
CREATE INDEX "content_category_id" ON "[{prefix}]content_to_categories" ("category_id");

