--
-- Categories Module MySQL Database for Phire CMS 2.0
--

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------

--
-- Table structure for table `categories`
--

CREATE TABLE IF NOT EXISTS `[{prefix}]categories` (
  `id` int(16) NOT NULL AUTO_INCREMENT,
  `parent_id` int(16),
  `name` varchar(255) NOT NULL,
  `uri` varchar(255) NOT NULL,
  `slug` varchar(255),
  `order` int(16),
  PRIMARY KEY (`id`),
  INDEX `category_parent_id` (`parent_id`),
  INDEX `category_name` (`name`),
  INDEX `category_uri` (`uri`),
  INDEX `category_slug` (`slug`),
  INDEX `category_order` (`order`),
  CONSTRAINT `fk_category_parent_id` FOREIGN KEY (`parent_id`) REFERENCES `[{prefix}]categories` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB  DEFAULT CHARSET=utf8 AUTO_INCREMENT=11001;

-- --------------------------------------------------------

SET FOREIGN_KEY_CHECKS = 1;
