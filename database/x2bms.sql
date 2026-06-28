/*
 Navicat Premium Data Transfer

 Source Server         : 0-lc
 Source Server Type    : MariaDB
 Source Server Version : 120302
 Source Host           : localhost:3306
 Source Schema         : x2bms

 Target Server Type    : MariaDB
 Target Server Version : 120302
 File Encoding         : 65001

 Date: 29/06/2026 00:36:52
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for access_cards
-- ----------------------------
DROP TABLE IF EXISTS `access_cards`;
CREATE TABLE `access_cards`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `resident_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `apartment_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `card_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'rfid',
  `is_biometric` tinyint(1) NOT NULL DEFAULT 0,
  `valid_from` date NULL DEFAULT NULL,
  `valid_to` date NULL DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `access_cards_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `access_cards_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `access_cards_resident_id_foreign`(`resident_id`) USING BTREE,
  INDEX `access_cards_apartment_id_foreign`(`apartment_id`) USING BTREE,
  CONSTRAINT `access_cards_apartment_id_foreign` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `access_cards_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `access_cards_resident_id_foreign` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `access_cards_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 121 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of access_cards
-- ----------------------------
INSERT INTO `access_cards` VALUES (1, 1, 1, 1, 1, 'RFID-100000', 'biometric', 1, '2025-01-01', '2026-12-31', 'revoked', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (2, 1, 1, 2, 2, 'RFID-100001', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (3, 1, 1, 3, 3, 'RFID-100002', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (4, 1, 1, 4, 4, 'RFID-100003', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (5, 1, 1, 5, 5, 'RFID-100004', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (6, 1, 1, 6, 6, 'RFID-100005', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (7, 1, 1, 7, 7, 'RFID-100006', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (8, 1, 1, 8, 8, 'RFID-100007', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (9, 1, 1, 9, 9, 'RFID-100008', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (10, 1, 1, 10, 10, 'RFID-100009', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (11, 1, 1, 11, 11, 'RFID-100010', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (12, 1, 1, 12, 12, 'RFID-100011', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (13, 1, 1, 13, 13, 'RFID-100012', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (14, 1, 1, 14, 14, 'RFID-100013', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (15, 1, 1, 15, 15, 'RFID-100014', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (16, 1, 1, 16, 16, 'RFID-100015', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (17, 1, 1, 17, 17, 'RFID-100016', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (18, 1, 1, 18, 18, 'RFID-100017', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (19, 1, 1, 19, 19, 'RFID-100018', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (20, 1, 1, 20, 20, 'RFID-100019', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (21, 1, 1, 21, 21, 'RFID-100020', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (22, 1, 1, 22, 22, 'RFID-100021', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (23, 1, 1, 23, 23, 'RFID-100022', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (24, 1, 1, 24, 24, 'RFID-100023', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (25, 1, 1, 25, 25, 'RFID-100024', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (26, 1, 1, 26, 26, 'RFID-100025', 'rfid', 0, '2025-01-01', '2026-12-31', 'revoked', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (27, 1, 1, 27, 27, 'RFID-100026', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (28, 1, 1, 28, 28, 'RFID-100027', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (29, 1, 1, 29, 29, 'RFID-100028', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (30, 1, 1, 30, 30, 'RFID-100029', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (31, 1, 1, 31, 31, 'RFID-100030', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (32, 1, 1, 32, 32, 'RFID-100031', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (33, 1, 1, 33, 33, 'RFID-100032', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (34, 1, 1, 34, 34, 'RFID-100033', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (35, 1, 1, 35, 35, 'RFID-100034', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (36, 1, 1, 36, 36, 'RFID-100035', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (37, 1, 1, 37, 37, 'RFID-100036', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (38, 1, 1, 38, 38, 'RFID-100037', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (39, 1, 1, 39, 39, 'RFID-100038', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (40, 1, 1, 40, 40, 'RFID-100039', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (41, 1, 1, 41, 41, 'RFID-100040', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (42, 1, 1, 42, 42, 'RFID-100041', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (43, 1, 1, 43, 43, 'RFID-100042', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (44, 1, 1, 44, 44, 'RFID-100043', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (45, 1, 1, 45, 45, 'RFID-100044', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (46, 1, 1, 46, 46, 'RFID-100045', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (47, 1, 1, 47, 47, 'RFID-100046', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (48, 1, 1, 48, 48, 'RFID-100047', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (49, 1, 1, 49, 49, 'RFID-100048', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (50, 1, 1, 50, 50, 'RFID-100049', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (51, 1, 1, 51, 51, 'RFID-100050', 'rfid', 0, '2025-01-01', '2026-12-31', 'revoked', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (52, 1, 1, 52, 52, 'RFID-100051', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (53, 1, 1, 53, 53, 'RFID-100052', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (54, 1, 1, 54, 54, 'RFID-100053', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (55, 1, 1, 55, 55, 'RFID-100054', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (56, 1, 1, 56, 56, 'RFID-100055', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (57, 1, 1, 57, 57, 'RFID-100056', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (58, 1, 1, 58, 58, 'RFID-100057', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (59, 1, 1, 59, 59, 'RFID-100058', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (60, 1, 1, 60, 60, 'RFID-100059', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (61, 1, 1, 61, 61, 'RFID-100060', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (62, 1, 1, 62, 62, 'RFID-100061', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (63, 1, 1, 63, 63, 'RFID-100062', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (64, 1, 1, 64, 64, 'RFID-100063', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (65, 1, 1, 65, 65, 'RFID-100064', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (66, 1, 1, 66, 66, 'RFID-100065', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (67, 1, 1, 67, 67, 'RFID-100066', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (68, 1, 1, 68, 68, 'RFID-100067', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (69, 1, 1, 69, 69, 'RFID-100068', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (70, 1, 1, 70, 70, 'RFID-100069', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (71, 1, 1, 71, 71, 'RFID-100070', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (72, 1, 1, 72, 72, 'RFID-100071', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (73, 1, 1, 73, 73, 'RFID-100072', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (74, 1, 1, 74, 74, 'RFID-100073', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (75, 1, 1, 75, 75, 'RFID-100074', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (76, 1, 1, 76, 76, 'RFID-100075', 'rfid', 0, '2025-01-01', '2026-12-31', 'revoked', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (77, 1, 1, 77, 77, 'RFID-100076', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (78, 1, 1, 78, 78, 'RFID-100077', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (79, 1, 1, 79, 79, 'RFID-100078', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (80, 1, 1, 80, 80, 'RFID-100079', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (81, 1, 1, 81, 81, 'RFID-100080', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (82, 1, 1, 82, 82, 'RFID-100081', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (83, 1, 1, 83, 83, 'RFID-100082', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (84, 1, 1, 84, 84, 'RFID-100083', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (85, 1, 1, 85, 85, 'RFID-100084', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (86, 1, 1, 86, 86, 'RFID-100085', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (87, 1, 1, 87, 87, 'RFID-100086', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (88, 1, 1, 88, 88, 'RFID-100087', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (89, 1, 1, 89, 89, 'RFID-100088', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (90, 1, 1, 90, 90, 'RFID-100089', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (91, 1, 1, 91, 91, 'RFID-100090', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (92, 1, 1, 92, 92, 'RFID-100091', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (93, 1, 1, 93, 93, 'RFID-100092', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (94, 1, 1, 94, 94, 'RFID-100093', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (95, 1, 1, 95, 95, 'RFID-100094', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (96, 1, 1, 96, 96, 'RFID-100095', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (97, 1, 1, 97, 97, 'RFID-100096', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (98, 1, 1, 98, 98, 'RFID-100097', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (99, 1, 1, 99, 99, 'RFID-100098', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (100, 1, 1, 100, 100, 'RFID-100099', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (101, 1, 1, 101, 101, 'RFID-100100', 'rfid', 0, '2025-01-01', '2026-12-31', 'revoked', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (102, 1, 1, 102, 102, 'RFID-100101', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (103, 1, 1, 103, 103, 'RFID-100102', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (104, 1, 1, 104, 104, 'RFID-100103', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (105, 1, 1, 105, 105, 'RFID-100104', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (106, 1, 1, 106, 106, 'RFID-100105', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (107, 1, 1, 107, 107, 'RFID-100106', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (108, 1, 1, 108, 108, 'RFID-100107', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (109, 1, 1, 109, 109, 'RFID-100108', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (110, 1, 1, 110, 110, 'RFID-100109', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (111, 1, 1, 111, 111, 'RFID-100110', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (112, 1, 1, 112, 112, 'RFID-100111', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (113, 1, 1, 113, 113, 'RFID-100112', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (114, 1, 1, 114, 114, 'RFID-100113', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (115, 1, 1, 115, 115, 'RFID-100114', 'biometric', 1, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (116, 1, 1, 116, 116, 'RFID-100115', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (117, 1, 1, 117, 117, 'RFID-100116', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (118, 1, 1, 118, 118, 'RFID-100117', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (119, 1, 1, 119, 119, 'RFID-100118', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `access_cards` VALUES (120, 1, 1, 120, 120, 'RFID-100119', 'rfid', 0, '2025-01-01', '2026-12-31', 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');

-- ----------------------------
-- Table structure for activity_log
-- ----------------------------
DROP TABLE IF EXISTS `activity_log`;
CREATE TABLE `activity_log`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `description` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `event` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `causer_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `causer_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `attribute_changes` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `subject`(`subject_type`, `subject_id`) USING BTREE,
  INDEX `causer`(`causer_type`, `causer_id`) USING BTREE,
  INDEX `activity_log_log_name_index`(`log_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of activity_log
-- ----------------------------

-- ----------------------------
-- Table structure for ai_suggestions
-- ----------------------------
DROP TABLE IF EXISTS `ai_suggestions`;
CREATE TABLE `ai_suggestions`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `context` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'operational_dashboard',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `detail` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'suggested',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `ai_suggestions_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `ai_suggestions_building_id_foreign`(`building_id`) USING BTREE,
  CONSTRAINT `ai_suggestions_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `ai_suggestions_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 4 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ai_suggestions
-- ----------------------------
INSERT INTO `ai_suggestions` VALUES (1, 1, 1, 'operational_dashboard', 'Ưu tiên xử lý 6 phản ánh kỹ thuật quá hạn SLA', 'Tập trung nhân sự Kỹ thuật trong hôm nay', 'suggested', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `ai_suggestions` VALUES (2, 1, 1, 'operational_dashboard', 'Gửi nhắc thanh toán cho 12 căn công nợ đến hạn', 'Dự kiến thu thêm ~96 triệu', 'suggested', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `ai_suggestions` VALUES (3, 1, 1, 'operational_dashboard', 'Lên lịch bảo trì máy bơm nước số 2', 'Tránh rủi ro mất nước cuối tuần', 'suggested', '2026-06-28 17:32:33', '2026-06-28 17:32:33');

-- ----------------------------
-- Table structure for apartments
-- ----------------------------
DROP TABLE IF EXISTS `apartments`;
CREATE TABLE `apartments`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `floor_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'occupied',
  `area_sqm` decimal(8, 2) NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `apartments_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `apartments_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `apartments_floor_id_foreign`(`floor_id`) USING BTREE,
  CONSTRAINT `apartments_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `apartments_floor_id_foreign` FOREIGN KEY (`floor_id`) REFERENCES `floors` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `apartments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 121 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of apartments
-- ----------------------------
INSERT INTO `apartments` VALUES (1, 1, 1, 1, 'A-0101', 'occupied', 70.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (2, 1, 1, 1, 'A-0102', 'occupied', 75.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (3, 1, 1, 1, 'A-0103', 'occupied', 80.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (4, 1, 1, 1, 'A-0104', 'occupied', 85.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (5, 1, 1, 1, 'A-0105', 'occupied', 90.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (6, 1, 1, 1, 'A-0106', 'occupied', 95.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (7, 1, 1, 2, 'A-0201', 'occupied', 70.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (8, 1, 1, 2, 'A-0202', 'occupied', 75.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (9, 1, 1, 2, 'A-0203', 'occupied', 80.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (10, 1, 1, 2, 'A-0204', 'occupied', 85.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (11, 1, 1, 2, 'A-0205', 'occupied', 90.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (12, 1, 1, 2, 'A-0206', 'occupied', 95.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (13, 1, 1, 3, 'A-0301', 'occupied', 70.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (14, 1, 1, 3, 'A-0302', 'occupied', 75.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (15, 1, 1, 3, 'A-0303', 'occupied', 80.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (16, 1, 1, 3, 'A-0304', 'occupied', 85.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (17, 1, 1, 3, 'A-0305', 'occupied', 90.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (18, 1, 1, 3, 'A-0306', 'occupied', 95.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (19, 1, 1, 4, 'A-0401', 'occupied', 70.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (20, 1, 1, 4, 'A-0402', 'occupied', 75.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (21, 1, 1, 4, 'A-0403', 'occupied', 80.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (22, 1, 1, 4, 'A-0404', 'occupied', 85.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (23, 1, 1, 4, 'A-0405', 'occupied', 90.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (24, 1, 1, 4, 'A-0406', 'occupied', 95.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (25, 1, 1, 5, 'A-0501', 'occupied', 70.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (26, 1, 1, 5, 'A-0502', 'occupied', 75.00, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `apartments` VALUES (27, 1, 1, 5, 'A-0503', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (28, 1, 1, 5, 'A-0504', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (29, 1, 1, 5, 'A-0505', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (30, 1, 1, 5, 'A-0506', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (31, 1, 1, 6, 'A-0601', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (32, 1, 1, 6, 'A-0602', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (33, 1, 1, 6, 'A-0603', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (34, 1, 1, 6, 'A-0604', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (35, 1, 1, 6, 'A-0605', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (36, 1, 1, 6, 'A-0606', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (37, 1, 1, 7, 'A-0701', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (38, 1, 1, 7, 'A-0702', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (39, 1, 1, 7, 'A-0703', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (40, 1, 1, 7, 'A-0704', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (41, 1, 1, 7, 'A-0705', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (42, 1, 1, 7, 'A-0706', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (43, 1, 1, 8, 'A-0801', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (44, 1, 1, 8, 'A-0802', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (45, 1, 1, 8, 'A-0803', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (46, 1, 1, 8, 'A-0804', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (47, 1, 1, 8, 'A-0805', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (48, 1, 1, 8, 'A-0806', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (49, 1, 1, 9, 'A-0901', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (50, 1, 1, 9, 'A-0902', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (51, 1, 1, 9, 'A-0903', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (52, 1, 1, 9, 'A-0904', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (53, 1, 1, 9, 'A-0905', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (54, 1, 1, 9, 'A-0906', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (55, 1, 1, 10, 'A-1001', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (56, 1, 1, 10, 'A-1002', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (57, 1, 1, 10, 'A-1003', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (58, 1, 1, 10, 'A-1004', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (59, 1, 1, 10, 'A-1005', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (60, 1, 1, 10, 'A-1006', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (61, 1, 1, 11, 'A-1101', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (62, 1, 1, 11, 'A-1102', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (63, 1, 1, 11, 'A-1103', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (64, 1, 1, 11, 'A-1104', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (65, 1, 1, 11, 'A-1105', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (66, 1, 1, 11, 'A-1106', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (67, 1, 1, 12, 'A-1201', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (68, 1, 1, 12, 'A-1202', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (69, 1, 1, 12, 'A-1203', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (70, 1, 1, 12, 'A-1204', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (71, 1, 1, 12, 'A-1205', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (72, 1, 1, 12, 'A-1206', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (73, 1, 1, 13, 'A-1301', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (74, 1, 1, 13, 'A-1302', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (75, 1, 1, 13, 'A-1303', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (76, 1, 1, 13, 'A-1304', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (77, 1, 1, 13, 'A-1305', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (78, 1, 1, 13, 'A-1306', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (79, 1, 1, 14, 'A-1401', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (80, 1, 1, 14, 'A-1402', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (81, 1, 1, 14, 'A-1403', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (82, 1, 1, 14, 'A-1404', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (83, 1, 1, 14, 'A-1405', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (84, 1, 1, 14, 'A-1406', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (85, 1, 1, 15, 'A-1501', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (86, 1, 1, 15, 'A-1502', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (87, 1, 1, 15, 'A-1503', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (88, 1, 1, 15, 'A-1504', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (89, 1, 1, 15, 'A-1505', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (90, 1, 1, 15, 'A-1506', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (91, 1, 1, 16, 'A-1601', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (92, 1, 1, 16, 'A-1602', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (93, 1, 1, 16, 'A-1603', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (94, 1, 1, 16, 'A-1604', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (95, 1, 1, 16, 'A-1605', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (96, 1, 1, 16, 'A-1606', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (97, 1, 1, 17, 'A-1701', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (98, 1, 1, 17, 'A-1702', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (99, 1, 1, 17, 'A-1703', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (100, 1, 1, 17, 'A-1704', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (101, 1, 1, 17, 'A-1705', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (102, 1, 1, 17, 'A-1706', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (103, 1, 1, 18, 'A-1801', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (104, 1, 1, 18, 'A-1802', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (105, 1, 1, 18, 'A-1803', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (106, 1, 1, 18, 'A-1804', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (107, 1, 1, 18, 'A-1805', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (108, 1, 1, 18, 'A-1806', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (109, 1, 1, 19, 'A-1901', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (110, 1, 1, 19, 'A-1902', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (111, 1, 1, 19, 'A-1903', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (112, 1, 1, 19, 'A-1904', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (113, 1, 1, 19, 'A-1905', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (114, 1, 1, 19, 'A-1906', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (115, 1, 1, 20, 'A-2001', 'occupied', 70.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (116, 1, 1, 20, 'A-2002', 'occupied', 75.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (117, 1, 1, 20, 'A-2003', 'occupied', 80.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (118, 1, 1, 20, 'A-2004', 'occupied', 85.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (119, 1, 1, 20, 'A-2005', 'occupied', 90.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `apartments` VALUES (120, 1, 1, 20, 'A-2006', 'occupied', 95.00, '2026-06-28 17:32:32', '2026-06-28 17:32:32');

-- ----------------------------
-- Table structure for areas
-- ----------------------------
DROP TABLE IF EXISTS `areas`;
CREATE TABLE `areas`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'common',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `areas_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `areas_building_id_foreign`(`building_id`) USING BTREE,
  CONSTRAINT `areas_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `areas_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of areas
-- ----------------------------
INSERT INTO `areas` VALUES (1, 1, 1, 'BAI-XE', 'Bãi xe tầng hầm', 'parking', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `areas` VALUES (2, 1, 1, 'SANH', 'Sảnh chính', 'common', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `areas` VALUES (3, 1, 1, 'GYM', 'Phòng Gym', 'amenity', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `areas` VALUES (4, 1, 1, 'KY-THUAT', 'Phòng kỹ thuật', 'technical', '2026-06-28 17:32:31', '2026-06-28 17:32:31');

-- ----------------------------
-- Table structure for audit_logs
-- ----------------------------
DROP TABLE IF EXISTS `audit_logs`;
CREATE TABLE `audit_logs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `building_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `actor_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `action` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `audit_logs_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `audit_logs_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `audit_logs_user_id_foreign`(`user_id`) USING BTREE,
  CONSTRAINT `audit_logs_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `audit_logs_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `audit_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 3 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of audit_logs
-- ----------------------------
INSERT INTO `audit_logs` VALUES (1, 1, 1, 1, 'Nguyễn Minh Anh', 'statement.publish', 'App\\Models\\BillingPeriod', 7, 'Phát hành bảng kê phí kỳ T7/2026', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `audit_logs` VALUES (2, 1, 1, 1, 'Nguyễn Minh Anh', 'resident.approve', NULL, NULL, 'Duyệt hồ sơ cư dân: Hoàng Văn Sơn', '2026-06-28 17:32:48', '2026-06-28 17:32:48');

-- ----------------------------
-- Table structure for billing_periods
-- ----------------------------
DROP TABLE IF EXISTS `billing_periods`;
CREATE TABLE `billing_periods`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `period_month` date NOT NULL,
  `billed_amount` decimal(16, 2) NOT NULL DEFAULT 0.00,
  `collected_amount` decimal(16, 2) NOT NULL DEFAULT 0.00,
  `is_current` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `billing_periods_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `billing_periods_building_id_foreign`(`building_id`) USING BTREE,
  CONSTRAINT `billing_periods_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `billing_periods_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 8 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of billing_periods
-- ----------------------------
INSERT INTO `billing_periods` VALUES (1, 1, 1, '2026-01', 'T1/2026', '2026-01-01', 2150000000.00, 2100000000.00, 0, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `billing_periods` VALUES (2, 1, 1, '2026-02', 'T2/2026', '2026-02-01', 2220000000.00, 2180000000.00, 0, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `billing_periods` VALUES (3, 1, 1, '2026-03', 'T3/2026', '2026-03-01', 2300000000.00, 2250000000.00, 0, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `billing_periods` VALUES (4, 1, 1, '2026-04', 'T4/2026', '2026-04-01', 2360000000.00, 2310000000.00, 0, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `billing_periods` VALUES (5, 1, 1, '2026-05', 'T5/2026', '2026-05-01', 2430000000.00, 2380000000.00, 0, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `billing_periods` VALUES (6, 1, 1, '2026-06', 'T6/2026', '2026-06-01', 2500000000.00, 2420000000.00, 0, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `billing_periods` VALUES (7, 1, 1, '2026-07', 'T7/2026', '2026-07-01', 2546000000.00, 2450000000.00, 1, '2026-06-28 17:32:32', '2026-06-28 17:32:32');

-- ----------------------------
-- Table structure for buildings
-- ----------------------------
DROP TABLE IF EXISTS `buildings`;
CREATE TABLE `buildings`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `project_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `apartment_count` int(10) UNSIGNED NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `buildings_code_unique`(`code`) USING BTREE,
  INDEX `buildings_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `buildings_project_id_foreign`(`project_id`) USING BTREE,
  CONSTRAINT `buildings_project_id_foreign` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `buildings_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of buildings
-- ----------------------------
INSERT INTO `buildings` VALUES (1, 1, 1, 'SG-A', 'Sunshine Garden - Tòa A', 120, '2026-06-28 17:32:31', '2026-06-28 17:32:31');

-- ----------------------------
-- Table structure for cache
-- ----------------------------
DROP TABLE IF EXISTS `cache`;
CREATE TABLE `cache`  (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `value` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`) USING BTREE,
  INDEX `cache_expiration_index`(`expiration`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cache
-- ----------------------------
INSERT INTO `cache` VALUES ('x2-bms-cache-livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6', 'i:2;', 1782668018);
INSERT INTO `cache` VALUES ('x2-bms-cache-livewire-rate-limiter:16d36dff9abd246c67dfac3e63b993a169af77e6:timer', 'i:1782668018;', 1782668018);
INSERT INTO `cache` VALUES ('x2-bms-cache-spatie.permission.cache', 'a:3:{s:5:\"alias\";a:0:{}s:11:\"permissions\";a:0:{}s:5:\"roles\";a:0:{}}', 1782754359);

-- ----------------------------
-- Table structure for cache_locks
-- ----------------------------
DROP TABLE IF EXISTS `cache_locks`;
CREATE TABLE `cache_locks`  (
  `key` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `owner` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `expiration` bigint(20) NOT NULL,
  PRIMARY KEY (`key`) USING BTREE,
  INDEX `cache_locks_expiration_index`(`expiration`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of cache_locks
-- ----------------------------

-- ----------------------------
-- Table structure for debts
-- ----------------------------
DROP TABLE IF EXISTS `debts`;
CREATE TABLE `debts`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `apartment_id` bigint(20) UNSIGNED NOT NULL,
  `amount` decimal(16, 2) NOT NULL DEFAULT 0.00,
  `due_date` date NULL DEFAULT NULL,
  `is_overdue` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `debts_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `debts_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `debts_apartment_id_foreign`(`apartment_id`) USING BTREE,
  CONSTRAINT `debts_apartment_id_foreign` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `debts_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `debts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of debts
-- ----------------------------
INSERT INTO `debts` VALUES (1, 1, 1, 13, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `debts` VALUES (2, 1, 1, 14, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `debts` VALUES (3, 1, 1, 15, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `debts` VALUES (4, 1, 1, 16, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `debts` VALUES (5, 1, 1, 17, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `debts` VALUES (6, 1, 1, 18, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `debts` VALUES (7, 1, 1, 19, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `debts` VALUES (8, 1, 1, 20, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `debts` VALUES (9, 1, 1, 21, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `debts` VALUES (10, 1, 1, 22, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `debts` VALUES (11, 1, 1, 23, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `debts` VALUES (12, 1, 1, 24, 8000000.00, '2026-07-10', 1, '2026-06-28 17:32:33', '2026-06-28 17:32:33');

-- ----------------------------
-- Table structure for departments
-- ----------------------------
DROP TABLE IF EXISTS `departments`;
CREATE TABLE `departments`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `departments_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `departments_building_id_foreign`(`building_id`) USING BTREE,
  CONSTRAINT `departments_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `departments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of departments
-- ----------------------------
INSERT INTO `departments` VALUES (1, 1, 1, 'KT', 'Kỹ thuật', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `departments` VALUES (2, 1, 1, 'AN', 'An ninh', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `departments` VALUES (3, 1, 1, 'VS', 'Vệ sinh', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `departments` VALUES (4, 1, 1, 'CS', 'CSKH', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `departments` VALUES (5, 1, 1, 'TC', 'Tài chính', '2026-06-28 17:32:31', '2026-06-28 17:32:31');

-- ----------------------------
-- Table structure for failed_jobs
-- ----------------------------
DROP TABLE IF EXISTS `failed_jobs`;
CREATE TABLE `failed_jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `uuid` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `connection` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `exception` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `failed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `failed_jobs_uuid_unique`(`uuid`) USING BTREE,
  INDEX `failed_jobs_connection_queue_failed_at_index`(`connection`, `queue`, `failed_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of failed_jobs
-- ----------------------------

-- ----------------------------
-- Table structure for feedback_categories
-- ----------------------------
DROP TABLE IF EXISTS `feedback_categories`;
CREATE TABLE `feedback_categories`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `color` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `feedback_categories_tenant_id_foreign`(`tenant_id`) USING BTREE,
  CONSTRAINT `feedback_categories_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 6 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of feedback_categories
-- ----------------------------
INSERT INTO `feedback_categories` VALUES (1, 1, 'KT', 'Kỹ thuật', '#2563eb', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_categories` VALUES (2, 1, 'VS', 'Vệ sinh', '#0d9488', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_categories` VALUES (3, 1, 'AN', 'An ninh', '#f59e0b', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_categories` VALUES (4, 1, 'TI', 'Tiện ích', '#8b5cf6', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_categories` VALUES (5, 1, 'KH', 'Khác', '#94a3b8', '2026-06-28 17:32:33', '2026-06-28 17:32:33');

-- ----------------------------
-- Table structure for feedback_requests
-- ----------------------------
DROP TABLE IF EXISTS `feedback_requests`;
CREATE TABLE `feedback_requests`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `feedback_category_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `apartment_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'new',
  `priority` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `feedback_requests_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `feedback_requests_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `feedback_requests_feedback_category_id_foreign`(`feedback_category_id`) USING BTREE,
  INDEX `feedback_requests_apartment_id_foreign`(`apartment_id`) USING BTREE,
  CONSTRAINT `feedback_requests_apartment_id_foreign` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `feedback_requests_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `feedback_requests_feedback_category_id_foreign` FOREIGN KEY (`feedback_category_id`) REFERENCES `feedback_categories` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `feedback_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 133 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of feedback_requests
-- ----------------------------
INSERT INTO `feedback_requests` VALUES (1, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #1', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (2, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #2', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (3, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #3', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (4, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #4', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (5, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #5', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (6, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #6', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (7, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #7', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (8, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #8', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (9, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #9', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (10, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #10', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (11, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #11', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (12, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #12', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (13, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #13', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (14, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #14', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (15, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #15', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (16, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #16', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (17, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #17', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (18, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #18', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (19, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #19', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (20, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #20', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (21, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #21', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (22, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #22', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (23, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #23', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (24, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #24', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (25, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #25', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (26, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #26', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (27, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #27', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (28, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #28', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (29, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #29', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (30, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #30', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (31, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #31', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (32, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #32', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (33, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #33', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (34, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #34', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (35, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #35', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (36, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #36', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (37, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #37', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (38, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #38', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (39, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #39', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (40, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #40', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (41, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #41', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (42, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #42', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (43, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #43', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (44, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #44', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (45, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #45', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (46, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #46', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (47, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #47', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (48, 1, 1, 1, NULL, 'Phản ánh Kỹ thuật #48', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (49, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #1', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (50, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #2', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (51, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #3', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (52, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #4', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (53, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #5', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (54, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #6', 'new', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (55, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #7', 'in_progress', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (56, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #8', 'assigned', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (57, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #9', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (58, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #10', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (59, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #11', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (60, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #12', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (61, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #13', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (62, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #14', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (63, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #15', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (64, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #16', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (65, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #17', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (66, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #18', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (67, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #19', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (68, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #20', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (69, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #21', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (70, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #22', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (71, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #23', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (72, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #24', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (73, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #25', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (74, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #26', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (75, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #27', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (76, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #28', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (77, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #29', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (78, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #30', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (79, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #31', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (80, 1, 1, 2, NULL, 'Phản ánh Vệ sinh #32', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (81, 1, 1, 3, NULL, 'Phản ánh An ninh #1', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (82, 1, 1, 3, NULL, 'Phản ánh An ninh #2', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (83, 1, 1, 3, NULL, 'Phản ánh An ninh #3', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (84, 1, 1, 3, NULL, 'Phản ánh An ninh #4', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (85, 1, 1, 3, NULL, 'Phản ánh An ninh #5', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (86, 1, 1, 3, NULL, 'Phản ánh An ninh #6', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (87, 1, 1, 3, NULL, 'Phản ánh An ninh #7', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (88, 1, 1, 3, NULL, 'Phản ánh An ninh #8', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (89, 1, 1, 3, NULL, 'Phản ánh An ninh #9', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (90, 1, 1, 3, NULL, 'Phản ánh An ninh #10', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (91, 1, 1, 3, NULL, 'Phản ánh An ninh #11', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (92, 1, 1, 3, NULL, 'Phản ánh An ninh #12', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (93, 1, 1, 3, NULL, 'Phản ánh An ninh #13', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (94, 1, 1, 3, NULL, 'Phản ánh An ninh #14', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (95, 1, 1, 3, NULL, 'Phản ánh An ninh #15', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (96, 1, 1, 3, NULL, 'Phản ánh An ninh #16', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (97, 1, 1, 3, NULL, 'Phản ánh An ninh #17', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (98, 1, 1, 3, NULL, 'Phản ánh An ninh #18', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (99, 1, 1, 3, NULL, 'Phản ánh An ninh #19', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (100, 1, 1, 3, NULL, 'Phản ánh An ninh #20', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (101, 1, 1, 3, NULL, 'Phản ánh An ninh #21', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (102, 1, 1, 3, NULL, 'Phản ánh An ninh #22', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (103, 1, 1, 4, NULL, 'Phản ánh Tiện ích #1', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (104, 1, 1, 4, NULL, 'Phản ánh Tiện ích #2', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (105, 1, 1, 4, NULL, 'Phản ánh Tiện ích #3', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (106, 1, 1, 4, NULL, 'Phản ánh Tiện ích #4', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (107, 1, 1, 4, NULL, 'Phản ánh Tiện ích #5', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (108, 1, 1, 4, NULL, 'Phản ánh Tiện ích #6', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (109, 1, 1, 4, NULL, 'Phản ánh Tiện ích #7', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (110, 1, 1, 4, NULL, 'Phản ánh Tiện ích #8', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (111, 1, 1, 4, NULL, 'Phản ánh Tiện ích #9', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (112, 1, 1, 4, NULL, 'Phản ánh Tiện ích #10', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (113, 1, 1, 4, NULL, 'Phản ánh Tiện ích #11', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (114, 1, 1, 4, NULL, 'Phản ánh Tiện ích #12', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (115, 1, 1, 4, NULL, 'Phản ánh Tiện ích #13', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (116, 1, 1, 4, NULL, 'Phản ánh Tiện ích #14', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (117, 1, 1, 4, NULL, 'Phản ánh Tiện ích #15', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (118, 1, 1, 4, NULL, 'Phản ánh Tiện ích #16', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (119, 1, 1, 4, NULL, 'Phản ánh Tiện ích #17', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (120, 1, 1, 4, NULL, 'Phản ánh Tiện ích #18', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (121, 1, 1, 5, NULL, 'Phản ánh Khác #1', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (122, 1, 1, 5, NULL, 'Phản ánh Khác #2', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (123, 1, 1, 5, NULL, 'Phản ánh Khác #3', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (124, 1, 1, 5, NULL, 'Phản ánh Khác #4', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (125, 1, 1, 5, NULL, 'Phản ánh Khác #5', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (126, 1, 1, 5, NULL, 'Phản ánh Khác #6', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (127, 1, 1, 5, NULL, 'Phản ánh Khác #7', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (128, 1, 1, 5, NULL, 'Phản ánh Khác #8', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (129, 1, 1, 5, NULL, 'Phản ánh Khác #9', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (130, 1, 1, 5, NULL, 'Phản ánh Khác #10', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (131, 1, 1, 5, NULL, 'Phản ánh Khác #11', 'closed', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `feedback_requests` VALUES (132, 1, 1, 5, NULL, 'Phản ánh Khác #12', 'resolved', 'normal', '2026-06-28 17:32:33', '2026-06-28 17:32:33');

-- ----------------------------
-- Table structure for floors
-- ----------------------------
DROP TABLE IF EXISTS `floors`;
CREATE TABLE `floors`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `level` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `floors_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `floors_building_id_foreign`(`building_id`) USING BTREE,
  CONSTRAINT `floors_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `floors_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 21 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of floors
-- ----------------------------
INSERT INTO `floors` VALUES (1, 1, 1, 'F01', 'Tầng 1', 1, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (2, 1, 1, 'F02', 'Tầng 2', 2, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (3, 1, 1, 'F03', 'Tầng 3', 3, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (4, 1, 1, 'F04', 'Tầng 4', 4, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (5, 1, 1, 'F05', 'Tầng 5', 5, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (6, 1, 1, 'F06', 'Tầng 6', 6, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (7, 1, 1, 'F07', 'Tầng 7', 7, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (8, 1, 1, 'F08', 'Tầng 8', 8, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (9, 1, 1, 'F09', 'Tầng 9', 9, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (10, 1, 1, 'F10', 'Tầng 10', 10, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (11, 1, 1, 'F11', 'Tầng 11', 11, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (12, 1, 1, 'F12', 'Tầng 12', 12, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (13, 1, 1, 'F13', 'Tầng 13', 13, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (14, 1, 1, 'F14', 'Tầng 14', 14, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (15, 1, 1, 'F15', 'Tầng 15', 15, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (16, 1, 1, 'F16', 'Tầng 16', 16, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (17, 1, 1, 'F17', 'Tầng 17', 17, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (18, 1, 1, 'F18', 'Tầng 18', 18, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (19, 1, 1, 'F19', 'Tầng 19', 19, '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `floors` VALUES (20, 1, 1, 'F20', 'Tầng 20', 20, '2026-06-28 17:32:31', '2026-06-28 17:32:31');

-- ----------------------------
-- Table structure for ioc_alerts
-- ----------------------------
DROP TABLE IF EXISTS `ioc_alerts`;
CREATE TABLE `ioc_alerts`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `source` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `severity` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'warning',
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `ioc_alerts_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `ioc_alerts_building_id_foreign`(`building_id`) USING BTREE,
  CONSTRAINT `ioc_alerts_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `ioc_alerts_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 5 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of ioc_alerts
-- ----------------------------
INSERT INTO `ioc_alerts` VALUES (1, 1, 1, 'device', 'critical', 'Nhiệt độ phòng kỹ thuật vượt ngưỡng', 'open', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `ioc_alerts` VALUES (2, 1, 1, 'camera', 'warning', 'Camera tầng 7 mất kết nối', 'open', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `ioc_alerts` VALUES (3, 1, 1, 'meter', 'warning', 'Đồng hồ nước Block B chênh lệch bất thường', 'open', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `ioc_alerts` VALUES (4, 1, 1, 'device', 'info', 'Máy bơm nước số 2 cần bảo trì', 'open', '2026-06-28 17:32:33', '2026-06-28 17:32:33');

-- ----------------------------
-- Table structure for job_batches
-- ----------------------------
DROP TABLE IF EXISTS `job_batches`;
CREATE TABLE `job_batches`  (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `total_jobs` int(11) NOT NULL,
  `pending_jobs` int(11) NOT NULL,
  `failed_jobs` int(11) NOT NULL,
  `failed_job_ids` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `options` mediumtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  `cancelled_at` int(11) NULL DEFAULT NULL,
  `created_at` int(11) NOT NULL,
  `finished_at` int(11) NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of job_batches
-- ----------------------------

-- ----------------------------
-- Table structure for jobs
-- ----------------------------
DROP TABLE IF EXISTS `jobs`;
CREATE TABLE `jobs`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `queue` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `attempts` smallint(5) UNSIGNED NOT NULL,
  `reserved_at` int(10) UNSIGNED NULL DEFAULT NULL,
  `available_at` int(10) UNSIGNED NOT NULL,
  `created_at` int(10) UNSIGNED NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `jobs_queue_index`(`queue`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of jobs
-- ----------------------------

-- ----------------------------
-- Table structure for media
-- ----------------------------
DROP TABLE IF EXISTS `media`;
CREATE TABLE `media`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  `uuid` char(36) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `collection_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `mime_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `conversions_disk` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `size` bigint(20) UNSIGNED NOT NULL,
  `manipulations` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `custom_properties` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `generated_conversions` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `responsive_images` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `order_column` int(10) UNSIGNED NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `media_uuid_unique`(`uuid`) USING BTREE,
  INDEX `media_model_type_model_id_index`(`model_type`, `model_id`) USING BTREE,
  INDEX `media_order_column_index`(`order_column`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of media
-- ----------------------------

-- ----------------------------
-- Table structure for migrations
-- ----------------------------
DROP TABLE IF EXISTS `migrations`;
CREATE TABLE `migrations`  (
  `id` int(10) UNSIGNED NOT NULL AUTO_INCREMENT,
  `migration` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `batch` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 18 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of migrations
-- ----------------------------
INSERT INTO `migrations` VALUES (1, '0001_01_01_000000_create_users_table', 1);
INSERT INTO `migrations` VALUES (2, '0001_01_01_000001_create_cache_table', 1);
INSERT INTO `migrations` VALUES (3, '0001_01_01_000002_create_jobs_table', 1);
INSERT INTO `migrations` VALUES (4, '2022_12_14_083707_create_settings_table', 1);
INSERT INTO `migrations` VALUES (5, '2026_06_28_000001_create_org_structure_tables', 1);
INSERT INTO `migrations` VALUES (6, '2026_06_28_000002_add_scope_to_users_table', 1);
INSERT INTO `migrations` VALUES (7, '2026_06_28_000003_create_finance_tables', 1);
INSERT INTO `migrations` VALUES (8, '2026_06_28_000004_create_feedback_tables', 1);
INSERT INTO `migrations` VALUES (9, '2026_06_28_000005_create_operations_tables', 1);
INSERT INTO `migrations` VALUES (10, '2026_06_28_000006_create_audit_ai_tables', 1);
INSERT INTO `migrations` VALUES (11, '2026_06_28_000007_create_residents_and_structure', 1);
INSERT INTO `migrations` VALUES (12, '2026_06_28_163042_create_activity_log_table', 1);
INSERT INTO `migrations` VALUES (13, '2026_06_28_163042_create_permission_tables', 1);
INSERT INTO `migrations` VALUES (14, '2026_06_28_164945_create_media_table', 1);
INSERT INTO `migrations` VALUES (15, '2026_06_28_164948_create_schedule_monitor_tables', 1);
INSERT INTO `migrations` VALUES (16, '2026_06_28_164949_create_personal_access_tokens_table', 1);
INSERT INTO `migrations` VALUES (17, '2026_06_29_000001_create_vehicles_cards_approvals', 1);

-- ----------------------------
-- Table structure for model_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS `model_has_permissions`;
CREATE TABLE `model_has_permissions`  (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `model_id`, `model_type`) USING BTREE,
  INDEX `model_has_permissions_model_id_model_type_index`(`model_id`, `model_type`) USING BTREE,
  CONSTRAINT `model_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of model_has_permissions
-- ----------------------------

-- ----------------------------
-- Table structure for model_has_roles
-- ----------------------------
DROP TABLE IF EXISTS `model_has_roles`;
CREATE TABLE `model_has_roles`  (
  `role_id` bigint(20) UNSIGNED NOT NULL,
  `model_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `model_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`role_id`, `model_id`, `model_type`) USING BTREE,
  INDEX `model_has_roles_model_id_model_type_index`(`model_id`, `model_type`) USING BTREE,
  CONSTRAINT `model_has_roles_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of model_has_roles
-- ----------------------------
INSERT INTO `model_has_roles` VALUES (1, 'App\\Models\\User', 1);

-- ----------------------------
-- Table structure for monitored_scheduled_task_log_items
-- ----------------------------
DROP TABLE IF EXISTS `monitored_scheduled_task_log_items`;
CREATE TABLE `monitored_scheduled_task_log_items`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `monitored_scheduled_task_id` bigint(20) UNSIGNED NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `meta` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `fk_scheduled_task_id`(`monitored_scheduled_task_id`) USING BTREE,
  CONSTRAINT `fk_scheduled_task_id` FOREIGN KEY (`monitored_scheduled_task_id`) REFERENCES `monitored_scheduled_tasks` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of monitored_scheduled_task_log_items
-- ----------------------------

-- ----------------------------
-- Table structure for monitored_scheduled_tasks
-- ----------------------------
DROP TABLE IF EXISTS `monitored_scheduled_tasks`;
CREATE TABLE `monitored_scheduled_tasks`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `cron_expression` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `timezone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ping_url` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `last_started_at` datetime NULL DEFAULT NULL,
  `last_finished_at` datetime NULL DEFAULT NULL,
  `last_failed_at` datetime NULL DEFAULT NULL,
  `last_skipped_at` datetime NULL DEFAULT NULL,
  `registered_on_oh_dear_at` datetime NULL DEFAULT NULL,
  `last_pinged_at` datetime NULL DEFAULT NULL,
  `grace_time_in_minutes` int(11) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of monitored_scheduled_tasks
-- ----------------------------

-- ----------------------------
-- Table structure for password_reset_tokens
-- ----------------------------
DROP TABLE IF EXISTS `password_reset_tokens`;
CREATE TABLE `password_reset_tokens`  (
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`email`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of password_reset_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for permissions
-- ----------------------------
DROP TABLE IF EXISTS `permissions`;
CREATE TABLE `permissions`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `permissions_name_guard_name_unique`(`name`, `guard_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of permissions
-- ----------------------------

-- ----------------------------
-- Table structure for personal_access_tokens
-- ----------------------------
DROP TABLE IF EXISTS `personal_access_tokens`;
CREATE TABLE `personal_access_tokens`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tokenable_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `tokenable_id` bigint(20) UNSIGNED NOT NULL,
  `name` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `token` varchar(64) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `abilities` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `last_used_at` timestamp NULL DEFAULT NULL,
  `expires_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `personal_access_tokens_token_unique`(`token`) USING BTREE,
  INDEX `personal_access_tokens_tokenable_type_tokenable_id_index`(`tokenable_type`, `tokenable_id`) USING BTREE,
  INDEX `personal_access_tokens_expires_at_index`(`expires_at`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of personal_access_tokens
-- ----------------------------

-- ----------------------------
-- Table structure for projects
-- ----------------------------
DROP TABLE IF EXISTS `projects`;
CREATE TABLE `projects`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `projects_code_unique`(`code`) USING BTREE,
  INDEX `projects_tenant_id_foreign`(`tenant_id`) USING BTREE,
  CONSTRAINT `projects_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of projects
-- ----------------------------
INSERT INTO `projects` VALUES (1, 1, 'SUNSHINE-GARDEN', 'Sunshine Garden', '2026-06-28 17:32:31', '2026-06-28 17:32:31');

-- ----------------------------
-- Table structure for resident_apartment_relations
-- ----------------------------
DROP TABLE IF EXISTS `resident_apartment_relations`;
CREATE TABLE `resident_apartment_relations`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `resident_id` bigint(20) UNSIGNED NOT NULL,
  `apartment_id` bigint(20) UNSIGNED NOT NULL,
  `role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'owner',
  `is_primary` tinyint(1) NOT NULL DEFAULT 0,
  `start_date` date NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `resident_apartment_relations_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `resident_apartment_relations_resident_id_foreign`(`resident_id`) USING BTREE,
  INDEX `resident_apartment_relations_apartment_id_foreign`(`apartment_id`) USING BTREE,
  CONSTRAINT `resident_apartment_relations_apartment_id_foreign` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `resident_apartment_relations_resident_id_foreign` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `resident_apartment_relations_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 122 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of resident_apartment_relations
-- ----------------------------
INSERT INTO `resident_apartment_relations` VALUES (1, 1, 1, 1, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (2, 1, 2, 2, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (3, 1, 3, 3, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (4, 1, 4, 4, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (5, 1, 5, 5, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (6, 1, 6, 6, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (7, 1, 7, 7, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (8, 1, 8, 8, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (9, 1, 9, 9, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (10, 1, 10, 10, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (11, 1, 11, 11, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (12, 1, 12, 12, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (13, 1, 13, 13, 'member', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (14, 1, 14, 14, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (15, 1, 15, 15, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (16, 1, 16, 16, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (17, 1, 17, 17, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (18, 1, 18, 18, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (19, 1, 19, 19, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (20, 1, 20, 20, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (21, 1, 21, 21, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (22, 1, 22, 22, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (23, 1, 23, 23, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (24, 1, 24, 24, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (25, 1, 25, 25, 'member', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (26, 1, 26, 26, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (27, 1, 27, 27, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (28, 1, 28, 28, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (29, 1, 29, 29, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (30, 1, 30, 30, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (31, 1, 31, 31, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (32, 1, 32, 32, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (33, 1, 33, 33, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (34, 1, 34, 34, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (35, 1, 35, 35, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (36, 1, 36, 36, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (37, 1, 37, 37, 'member', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (38, 1, 38, 38, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (39, 1, 39, 39, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (40, 1, 40, 40, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (41, 1, 41, 41, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (42, 1, 42, 42, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (43, 1, 43, 43, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (44, 1, 44, 44, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (45, 1, 45, 45, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (46, 1, 46, 46, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (47, 1, 47, 47, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (48, 1, 48, 48, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (49, 1, 49, 49, 'member', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (50, 1, 50, 50, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (51, 1, 51, 51, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (52, 1, 52, 52, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (53, 1, 53, 53, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (54, 1, 54, 54, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (55, 1, 55, 55, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (56, 1, 56, 56, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (57, 1, 57, 57, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (58, 1, 58, 58, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (59, 1, 59, 59, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (60, 1, 60, 60, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (61, 1, 61, 61, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (62, 1, 62, 62, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (63, 1, 63, 63, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (64, 1, 64, 64, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (65, 1, 65, 65, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (66, 1, 66, 66, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (67, 1, 67, 67, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (68, 1, 68, 68, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (69, 1, 69, 69, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (70, 1, 70, 70, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (71, 1, 71, 71, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (72, 1, 72, 72, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (73, 1, 73, 73, 'member', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (74, 1, 74, 74, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (75, 1, 75, 75, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (76, 1, 76, 76, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (77, 1, 77, 77, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (78, 1, 78, 78, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (79, 1, 79, 79, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (80, 1, 80, 80, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (81, 1, 81, 81, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (82, 1, 82, 82, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (83, 1, 83, 83, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (84, 1, 84, 84, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (85, 1, 85, 85, 'member', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (86, 1, 86, 86, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (87, 1, 87, 87, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (88, 1, 88, 88, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (89, 1, 89, 89, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (90, 1, 90, 90, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (91, 1, 91, 91, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (92, 1, 92, 92, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (93, 1, 93, 93, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (94, 1, 94, 94, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (95, 1, 95, 95, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (96, 1, 96, 96, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (97, 1, 97, 97, 'member', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (98, 1, 98, 98, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (99, 1, 99, 99, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (100, 1, 100, 100, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (101, 1, 101, 101, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (102, 1, 102, 102, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (103, 1, 103, 103, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (104, 1, 104, 104, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (105, 1, 105, 105, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (106, 1, 106, 106, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (107, 1, 107, 107, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (108, 1, 108, 108, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (109, 1, 109, 109, 'member', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (110, 1, 110, 110, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (111, 1, 111, 111, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (112, 1, 112, 112, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (113, 1, 113, 113, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (114, 1, 114, 114, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (115, 1, 115, 115, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (116, 1, 116, 116, 'tenant', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (117, 1, 117, 117, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (118, 1, 118, 118, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (119, 1, 119, 119, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (120, 1, 120, 120, 'owner', 1, '2025-01-01', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_apartment_relations` VALUES (121, 1, 121, 36, 'owner', 1, '2026-06-28', '2026-06-28 17:32:48', '2026-06-28 17:32:48');

-- ----------------------------
-- Table structure for resident_approval_requests
-- ----------------------------
DROP TABLE IF EXISTS `resident_approval_requests`;
CREATE TABLE `resident_approval_requests`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `apartment_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `requested_role` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'owner',
  `match_score` tinyint(3) UNSIGNED NOT NULL DEFAULT 0,
  `document_count` smallint(5) UNSIGNED NOT NULL DEFAULT 0,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `submitted_at` timestamp NULL DEFAULT NULL,
  `note` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `resident_approval_requests_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `resident_approval_requests_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `resident_approval_requests_apartment_id_foreign`(`apartment_id`) USING BTREE,
  CONSTRAINT `resident_approval_requests_apartment_id_foreign` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `resident_approval_requests_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `resident_approval_requests_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of resident_approval_requests
-- ----------------------------
INSERT INTO `resident_approval_requests` VALUES (1, 1, 1, 1, 'Trần Thị Hồng', '0920000000', 'applicant1@x2bms.vn', 'owner', 92, 4, 'pending', '2026-06-27 17:32:32', NULL, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_approval_requests` VALUES (2, 1, 1, 8, 'Lê Văn Tài', '0920000001', 'applicant2@x2bms.vn', 'tenant', 78, 3, 'pending', '2026-06-26 17:32:32', NULL, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_approval_requests` VALUES (3, 1, 1, 15, 'Phạm Thu Hà', '0920000002', 'applicant3@x2bms.vn', 'owner', 65, 2, 'pending', '2026-06-25 17:32:32', NULL, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_approval_requests` VALUES (4, 1, 1, 22, 'Vũ Minh Khôi', '0920000003', 'applicant4@x2bms.vn', 'member', 88, 3, 'pending', '2026-06-24 17:32:32', NULL, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_approval_requests` VALUES (5, 1, 1, 29, 'Đỗ Thị Mai', '0920000004', 'applicant5@x2bms.vn', 'tenant', 54, 1, 'pending', '2026-06-23 17:32:32', NULL, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_approval_requests` VALUES (6, 1, 1, 36, 'Hoàng Văn Sơn', '0920000005', 'applicant6@x2bms.vn', 'owner', 95, 5, 'approved', '2026-06-22 17:32:32', NULL, '2026-06-28 17:32:32', '2026-06-28 17:32:48');
INSERT INTO `resident_approval_requests` VALUES (7, 1, 1, 43, 'Bùi Thị Lan', '0920000006', 'applicant7@x2bms.vn', 'member', 71, 2, 'pending', '2026-06-21 17:32:32', NULL, '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `resident_approval_requests` VALUES (8, 1, 1, 50, 'Ngô Quang Huy', '0920000007', 'applicant8@x2bms.vn', 'tenant', 83, 4, 'pending', '2026-06-20 17:32:32', NULL, '2026-06-28 17:32:32', '2026-06-28 17:32:32');

-- ----------------------------
-- Table structure for residents
-- ----------------------------
DROP TABLE IF EXISTS `residents`;
CREATE TABLE `residents`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `user_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `full_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `phone` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `id_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `residents_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `residents_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `residents_user_id_foreign`(`user_id`) USING BTREE,
  CONSTRAINT `residents_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `residents_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `residents_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 122 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of residents
-- ----------------------------
INSERT INTO `residents` VALUES (1, 1, 1, NULL, 'CD-0001', 'Nguyễn Văn An', '0910000000', 'cudan1@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (2, 1, 1, NULL, 'CD-0002', 'Nguyễn Văn Bình', '0910000001', 'cudan2@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (3, 1, 1, NULL, 'CD-0003', 'Nguyễn Văn Cường', '0910000002', 'cudan3@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (4, 1, 1, NULL, 'CD-0004', 'Nguyễn Văn Dung', '0910000003', 'cudan4@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (5, 1, 1, NULL, 'CD-0005', 'Nguyễn Văn Giang', '0910000004', 'cudan5@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (6, 1, 1, NULL, 'CD-0006', 'Nguyễn Văn Hà', '0910000005', 'cudan6@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (7, 1, 1, NULL, 'CD-0007', 'Nguyễn Văn Hùng', '0910000006', 'cudan7@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (8, 1, 1, NULL, 'CD-0008', 'Nguyễn Văn Lan', '0910000007', 'cudan8@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (9, 1, 1, NULL, 'CD-0009', 'Nguyễn Văn Minh', '0910000008', 'cudan9@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (10, 1, 1, NULL, 'CD-0010', 'Nguyễn Văn Nam', '0910000009', 'cudan10@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (11, 1, 1, NULL, 'CD-0011', 'Nguyễn Văn Phúc', '0910000010', 'cudan11@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (12, 1, 1, NULL, 'CD-0012', 'Nguyễn Văn Quân', '0910000011', 'cudan12@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (13, 1, 1, NULL, 'CD-0013', 'Nguyễn Văn Thảo', '0910000012', 'cudan13@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (14, 1, 1, NULL, 'CD-0014', 'Nguyễn Văn Vân', '0910000013', 'cudan14@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (15, 1, 1, NULL, 'CD-0015', 'Nguyễn Văn An', '0910000014', 'cudan15@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (16, 1, 1, NULL, 'CD-0016', 'Nguyễn Văn Bình', '0910000015', 'cudan16@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (17, 1, 1, NULL, 'CD-0017', 'Nguyễn Văn Cường', '0910000016', 'cudan17@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (18, 1, 1, NULL, 'CD-0018', 'Nguyễn Văn Dung', '0910000017', 'cudan18@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (19, 1, 1, NULL, 'CD-0019', 'Nguyễn Văn Giang', '0910000018', 'cudan19@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (20, 1, 1, NULL, 'CD-0020', 'Nguyễn Văn Hà', '0910000019', 'cudan20@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (21, 1, 1, NULL, 'CD-0021', 'Nguyễn Văn Hùng', '0910000020', 'cudan21@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (22, 1, 1, NULL, 'CD-0022', 'Nguyễn Văn Lan', '0910000021', 'cudan22@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (23, 1, 1, NULL, 'CD-0023', 'Nguyễn Văn Minh', '0910000022', 'cudan23@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (24, 1, 1, NULL, 'CD-0024', 'Nguyễn Văn Nam', '0910000023', 'cudan24@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (25, 1, 1, NULL, 'CD-0025', 'Nguyễn Văn Phúc', '0910000024', 'cudan25@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (26, 1, 1, NULL, 'CD-0026', 'Nguyễn Văn Quân', '0910000025', 'cudan26@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (27, 1, 1, NULL, 'CD-0027', 'Nguyễn Văn Thảo', '0910000026', 'cudan27@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (28, 1, 1, NULL, 'CD-0028', 'Nguyễn Văn Vân', '0910000027', 'cudan28@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (29, 1, 1, NULL, 'CD-0029', 'Nguyễn Văn An', '0910000028', 'cudan29@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (30, 1, 1, NULL, 'CD-0030', 'Nguyễn Văn Bình', '0910000029', 'cudan30@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (31, 1, 1, NULL, 'CD-0031', 'Nguyễn Văn Cường', '0910000030', 'cudan31@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (32, 1, 1, NULL, 'CD-0032', 'Nguyễn Văn Dung', '0910000031', 'cudan32@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (33, 1, 1, NULL, 'CD-0033', 'Nguyễn Văn Giang', '0910000032', 'cudan33@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (34, 1, 1, NULL, 'CD-0034', 'Nguyễn Văn Hà', '0910000033', 'cudan34@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (35, 1, 1, NULL, 'CD-0035', 'Nguyễn Văn Hùng', '0910000034', 'cudan35@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (36, 1, 1, NULL, 'CD-0036', 'Nguyễn Văn Lan', '0910000035', 'cudan36@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (37, 1, 1, NULL, 'CD-0037', 'Nguyễn Văn Minh', '0910000036', 'cudan37@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (38, 1, 1, NULL, 'CD-0038', 'Nguyễn Văn Nam', '0910000037', 'cudan38@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (39, 1, 1, NULL, 'CD-0039', 'Nguyễn Văn Phúc', '0910000038', 'cudan39@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (40, 1, 1, NULL, 'CD-0040', 'Nguyễn Văn Quân', '0910000039', 'cudan40@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (41, 1, 1, NULL, 'CD-0041', 'Nguyễn Văn Thảo', '0910000040', 'cudan41@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (42, 1, 1, NULL, 'CD-0042', 'Nguyễn Văn Vân', '0910000041', 'cudan42@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (43, 1, 1, NULL, 'CD-0043', 'Nguyễn Văn An', '0910000042', 'cudan43@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (44, 1, 1, NULL, 'CD-0044', 'Nguyễn Văn Bình', '0910000043', 'cudan44@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (45, 1, 1, NULL, 'CD-0045', 'Nguyễn Văn Cường', '0910000044', 'cudan45@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (46, 1, 1, NULL, 'CD-0046', 'Nguyễn Văn Dung', '0910000045', 'cudan46@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (47, 1, 1, NULL, 'CD-0047', 'Nguyễn Văn Giang', '0910000046', 'cudan47@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (48, 1, 1, NULL, 'CD-0048', 'Nguyễn Văn Hà', '0910000047', 'cudan48@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (49, 1, 1, NULL, 'CD-0049', 'Nguyễn Văn Hùng', '0910000048', 'cudan49@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (50, 1, 1, NULL, 'CD-0050', 'Nguyễn Văn Lan', '0910000049', 'cudan50@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (51, 1, 1, NULL, 'CD-0051', 'Nguyễn Văn Minh', '0910000050', 'cudan51@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (52, 1, 1, NULL, 'CD-0052', 'Nguyễn Văn Nam', '0910000051', 'cudan52@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (53, 1, 1, NULL, 'CD-0053', 'Nguyễn Văn Phúc', '0910000052', 'cudan53@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (54, 1, 1, NULL, 'CD-0054', 'Nguyễn Văn Quân', '0910000053', 'cudan54@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (55, 1, 1, NULL, 'CD-0055', 'Nguyễn Văn Thảo', '0910000054', 'cudan55@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (56, 1, 1, NULL, 'CD-0056', 'Nguyễn Văn Vân', '0910000055', 'cudan56@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (57, 1, 1, NULL, 'CD-0057', 'Nguyễn Văn An', '0910000056', 'cudan57@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (58, 1, 1, NULL, 'CD-0058', 'Nguyễn Văn Bình', '0910000057', 'cudan58@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (59, 1, 1, NULL, 'CD-0059', 'Nguyễn Văn Cường', '0910000058', 'cudan59@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (60, 1, 1, NULL, 'CD-0060', 'Nguyễn Văn Dung', '0910000059', 'cudan60@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (61, 1, 1, NULL, 'CD-0061', 'Nguyễn Văn Giang', '0910000060', 'cudan61@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (62, 1, 1, NULL, 'CD-0062', 'Nguyễn Văn Hà', '0910000061', 'cudan62@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (63, 1, 1, NULL, 'CD-0063', 'Nguyễn Văn Hùng', '0910000062', 'cudan63@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (64, 1, 1, NULL, 'CD-0064', 'Nguyễn Văn Lan', '0910000063', 'cudan64@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (65, 1, 1, NULL, 'CD-0065', 'Nguyễn Văn Minh', '0910000064', 'cudan65@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (66, 1, 1, NULL, 'CD-0066', 'Nguyễn Văn Nam', '0910000065', 'cudan66@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (67, 1, 1, NULL, 'CD-0067', 'Nguyễn Văn Phúc', '0910000066', 'cudan67@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (68, 1, 1, NULL, 'CD-0068', 'Nguyễn Văn Quân', '0910000067', 'cudan68@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (69, 1, 1, NULL, 'CD-0069', 'Nguyễn Văn Thảo', '0910000068', 'cudan69@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (70, 1, 1, NULL, 'CD-0070', 'Nguyễn Văn Vân', '0910000069', 'cudan70@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (71, 1, 1, NULL, 'CD-0071', 'Nguyễn Văn An', '0910000070', 'cudan71@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (72, 1, 1, NULL, 'CD-0072', 'Nguyễn Văn Bình', '0910000071', 'cudan72@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (73, 1, 1, NULL, 'CD-0073', 'Nguyễn Văn Cường', '0910000072', 'cudan73@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (74, 1, 1, NULL, 'CD-0074', 'Nguyễn Văn Dung', '0910000073', 'cudan74@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (75, 1, 1, NULL, 'CD-0075', 'Nguyễn Văn Giang', '0910000074', 'cudan75@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (76, 1, 1, NULL, 'CD-0076', 'Nguyễn Văn Hà', '0910000075', 'cudan76@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (77, 1, 1, NULL, 'CD-0077', 'Nguyễn Văn Hùng', '0910000076', 'cudan77@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (78, 1, 1, NULL, 'CD-0078', 'Nguyễn Văn Lan', '0910000077', 'cudan78@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (79, 1, 1, NULL, 'CD-0079', 'Nguyễn Văn Minh', '0910000078', 'cudan79@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (80, 1, 1, NULL, 'CD-0080', 'Nguyễn Văn Nam', '0910000079', 'cudan80@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (81, 1, 1, NULL, 'CD-0081', 'Nguyễn Văn Phúc', '0910000080', 'cudan81@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (82, 1, 1, NULL, 'CD-0082', 'Nguyễn Văn Quân', '0910000081', 'cudan82@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (83, 1, 1, NULL, 'CD-0083', 'Nguyễn Văn Thảo', '0910000082', 'cudan83@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (84, 1, 1, NULL, 'CD-0084', 'Nguyễn Văn Vân', '0910000083', 'cudan84@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (85, 1, 1, NULL, 'CD-0085', 'Nguyễn Văn An', '0910000084', 'cudan85@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (86, 1, 1, NULL, 'CD-0086', 'Nguyễn Văn Bình', '0910000085', 'cudan86@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (87, 1, 1, NULL, 'CD-0087', 'Nguyễn Văn Cường', '0910000086', 'cudan87@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (88, 1, 1, NULL, 'CD-0088', 'Nguyễn Văn Dung', '0910000087', 'cudan88@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (89, 1, 1, NULL, 'CD-0089', 'Nguyễn Văn Giang', '0910000088', 'cudan89@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (90, 1, 1, NULL, 'CD-0090', 'Nguyễn Văn Hà', '0910000089', 'cudan90@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (91, 1, 1, NULL, 'CD-0091', 'Nguyễn Văn Hùng', '0910000090', 'cudan91@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (92, 1, 1, NULL, 'CD-0092', 'Nguyễn Văn Lan', '0910000091', 'cudan92@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (93, 1, 1, NULL, 'CD-0093', 'Nguyễn Văn Minh', '0910000092', 'cudan93@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (94, 1, 1, NULL, 'CD-0094', 'Nguyễn Văn Nam', '0910000093', 'cudan94@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (95, 1, 1, NULL, 'CD-0095', 'Nguyễn Văn Phúc', '0910000094', 'cudan95@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (96, 1, 1, NULL, 'CD-0096', 'Nguyễn Văn Quân', '0910000095', 'cudan96@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (97, 1, 1, NULL, 'CD-0097', 'Nguyễn Văn Thảo', '0910000096', 'cudan97@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (98, 1, 1, NULL, 'CD-0098', 'Nguyễn Văn Vân', '0910000097', 'cudan98@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (99, 1, 1, NULL, 'CD-0099', 'Nguyễn Văn An', '0910000098', 'cudan99@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (100, 1, 1, NULL, 'CD-0100', 'Nguyễn Văn Bình', '0910000099', 'cudan100@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (101, 1, 1, NULL, 'CD-0101', 'Nguyễn Văn Cường', '0910000100', 'cudan101@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (102, 1, 1, NULL, 'CD-0102', 'Nguyễn Văn Dung', '0910000101', 'cudan102@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (103, 1, 1, NULL, 'CD-0103', 'Nguyễn Văn Giang', '0910000102', 'cudan103@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (104, 1, 1, NULL, 'CD-0104', 'Nguyễn Văn Hà', '0910000103', 'cudan104@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (105, 1, 1, NULL, 'CD-0105', 'Nguyễn Văn Hùng', '0910000104', 'cudan105@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (106, 1, 1, NULL, 'CD-0106', 'Nguyễn Văn Lan', '0910000105', 'cudan106@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (107, 1, 1, NULL, 'CD-0107', 'Nguyễn Văn Minh', '0910000106', 'cudan107@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (108, 1, 1, NULL, 'CD-0108', 'Nguyễn Văn Nam', '0910000107', 'cudan108@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (109, 1, 1, NULL, 'CD-0109', 'Nguyễn Văn Phúc', '0910000108', 'cudan109@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (110, 1, 1, NULL, 'CD-0110', 'Nguyễn Văn Quân', '0910000109', 'cudan110@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (111, 1, 1, NULL, 'CD-0111', 'Nguyễn Văn Thảo', '0910000110', 'cudan111@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (112, 1, 1, NULL, 'CD-0112', 'Nguyễn Văn Vân', '0910000111', 'cudan112@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (113, 1, 1, NULL, 'CD-0113', 'Nguyễn Văn An', '0910000112', 'cudan113@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (114, 1, 1, NULL, 'CD-0114', 'Nguyễn Văn Bình', '0910000113', 'cudan114@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (115, 1, 1, NULL, 'CD-0115', 'Nguyễn Văn Cường', '0910000114', 'cudan115@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (116, 1, 1, NULL, 'CD-0116', 'Nguyễn Văn Dung', '0910000115', 'cudan116@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (117, 1, 1, NULL, 'CD-0117', 'Nguyễn Văn Giang', '0910000116', 'cudan117@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (118, 1, 1, NULL, 'CD-0118', 'Nguyễn Văn Hà', '0910000117', 'cudan118@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (119, 1, 1, NULL, 'CD-0119', 'Nguyễn Văn Hùng', '0910000118', 'cudan119@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (120, 1, 1, NULL, 'CD-0120', 'Nguyễn Văn Lan', '0910000119', 'cudan120@x2bms.vn', NULL, 'active', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `residents` VALUES (121, 1, 1, NULL, 'CD-0121', 'Hoàng Văn Sơn', '0920000005', 'applicant6@x2bms.vn', NULL, 'active', '2026-06-28 17:32:48', '2026-06-28 17:32:48');

-- ----------------------------
-- Table structure for role_has_permissions
-- ----------------------------
DROP TABLE IF EXISTS `role_has_permissions`;
CREATE TABLE `role_has_permissions`  (
  `permission_id` bigint(20) UNSIGNED NOT NULL,
  `role_id` bigint(20) UNSIGNED NOT NULL,
  PRIMARY KEY (`permission_id`, `role_id`) USING BTREE,
  INDEX `role_has_permissions_role_id_foreign`(`role_id`) USING BTREE,
  CONSTRAINT `role_has_permissions_permission_id_foreign` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `role_has_permissions_role_id_foreign` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of role_has_permissions
-- ----------------------------

-- ----------------------------
-- Table structure for roles
-- ----------------------------
DROP TABLE IF EXISTS `roles`;
CREATE TABLE `roles`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `guard_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `roles_name_guard_name_unique`(`name`, `guard_name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of roles
-- ----------------------------
INSERT INTO `roles` VALUES (1, 'super_admin', 'web', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `roles` VALUES (2, 'bql_manager', 'web', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `roles` VALUES (3, 'accountant', 'web', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `roles` VALUES (4, 'technician', 'web', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `roles` VALUES (5, 'security', 'web', '2026-06-28 17:32:31', '2026-06-28 17:32:31');
INSERT INTO `roles` VALUES (6, 'resident_service', 'web', '2026-06-28 17:32:31', '2026-06-28 17:32:31');

-- ----------------------------
-- Table structure for sessions
-- ----------------------------
DROP TABLE IF EXISTS `sessions`;
CREATE TABLE `sessions`  (
  `id` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `user_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `ip_address` varchar(45) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `user_agent` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sessions_user_id_index`(`user_id`) USING BTREE,
  INDEX `sessions_last_activity_index`(`last_activity`) USING BTREE
) ENGINE = InnoDB CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sessions
-- ----------------------------
INSERT INTO `sessions` VALUES ('3Ws31nXx08TTJ6SS2k8UTDiHqjz0QbbhhMpRZYMR', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.7827.55 Safari/537.36', 'eyJfdG9rZW4iOiJzUm9xQUdoNVM4Z3BlS2RSNUM1QnFVTmRoZDY3eGxDcHhmMjFvaG42IiwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9yZXNpZGVudC1hcHByb3ZhbHMiLCJyb3V0ZSI6InJlc2lkZW50LWFwcHJvdmFscyJ9LCJfZmxhc2giOnsib2xkIjpbXSwibmV3IjpbXX0sImxvZ2luX3dlYl81OWJhMzZhZGRjMmIyZjk0MDE1ODBmMDE0YzdmNThlYTRlMzA5ODlkIjoxLCJwYXNzd29yZF9oYXNoX3dlYiI6IjJkOTI1ODAxYjBiYzY3ZTQ0N2I0NzhiNjhlZjVlNzE1YTQzZGNkOTlmZGM3ZjZhZDU4Y2RmNzEzYmMyMTdhY2QiLCJ0YWJsZXMiOnsiY2RkMDFmMjA3MTMwNGI1MDVkYzUzODRjYTkxMzVmMmRfY29sdW1ucyI6W3sidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJ0ZW5hbnQubmFtZSIsImxhYmVsIjoiVGVuYW50IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InByb2plY3QubmFtZSIsImxhYmVsIjoiUHJvamVjdCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJjb2RlIiwibGFiZWwiOiJDb2RlIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6Im5hbWUiLCJsYWJlbCI6Ik5hbWUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiYXBhcnRtZW50X2NvdW50IiwibGFiZWwiOiJBcGFydG1lbnQgY291bnQiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY3JlYXRlZF9hdCIsImxhYmVsIjoiQ3JlYXRlZCBhdCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjpmYWxzZSwiaXNUb2dnbGVhYmxlIjp0cnVlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOnRydWV9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJ1cGRhdGVkX2F0IiwibGFiZWwiOiJVcGRhdGVkIGF0IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOmZhbHNlLCJpc1RvZ2dsZWFibGUiOnRydWUsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6dHJ1ZX1dLCI3YzlkMzBjN2VhZGY3NGNjMGZiOGM1YTM1NDI4MzNlN19jb2x1bW5zIjpbeyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InRlbmFudC5uYW1lIiwibGFiZWwiOiJUZW5hbnQiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiYnVpbGRpbmdfaWQiLCJsYWJlbCI6IkJ1aWxkaW5nIGlkIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6ImNvZGUiLCJsYWJlbCI6IkNvZGUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoic3RhdHVzIiwibGFiZWwiOiJTdGF0dXMiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY3JlYXRlZF9hdCIsImxhYmVsIjoiQ3JlYXRlZCBhdCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjpmYWxzZSwiaXNUb2dnbGVhYmxlIjp0cnVlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOnRydWV9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJ1cGRhdGVkX2F0IiwibGFiZWwiOiJVcGRhdGVkIGF0IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOmZhbHNlLCJpc1RvZ2dsZWFibGUiOnRydWUsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6dHJ1ZX1dLCIxM2JjMzlhYTE5YWI4OTczNWQ5M2U3YTY4YWYyYWFhOV9jb2x1bW5zIjpbeyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InRlbmFudC5uYW1lIiwibGFiZWwiOiJUZW5hbnQiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiYnVpbGRpbmdfaWQiLCJsYWJlbCI6IkJ1aWxkaW5nIGlkIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InVzZXJfaWQiLCJsYWJlbCI6IlVzZXIgaWQiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY29kZSIsImxhYmVsIjoiQ29kZSIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJmdWxsX25hbWUiLCJsYWJlbCI6IkZ1bGwgbmFtZSIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjp0cnVlLCJpc1RvZ2dsZWFibGUiOmZhbHNlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOm51bGx9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJwaG9uZSIsImxhYmVsIjoiUGhvbmUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiZW1haWwiLCJsYWJlbCI6IkVtYWlsIGFkZHJlc3MiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiaWRfbm8iLCJsYWJlbCI6IklkIG5vIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6InN0YXR1cyIsImxhYmVsIjoiU3RhdHVzIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6ImNyZWF0ZWRfYXQiLCJsYWJlbCI6IkNyZWF0ZWQgYXQiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6ZmFsc2UsImlzVG9nZ2xlYWJsZSI6dHJ1ZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0Ijp0cnVlfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoidXBkYXRlZF9hdCIsImxhYmVsIjoiVXBkYXRlZCBhdCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjpmYWxzZSwiaXNUb2dnbGVhYmxlIjp0cnVlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOnRydWV9XSwiMGE2ODdkOWIzMDM5YWNhNGZhM2QyNDUzYWVlMGMzZDhfY29sdW1ucyI6W3sidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJjb2RlIiwibGFiZWwiOiJDb2RlIiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOnRydWUsImlzVG9nZ2xlYWJsZSI6ZmFsc2UsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6bnVsbH0seyJ0eXBlIjoiY29sdW1uIiwibmFtZSI6Im5hbWUiLCJsYWJlbCI6Ik5hbWUiLCJpc0hpZGRlbiI6ZmFsc2UsImlzVG9nZ2xlZCI6dHJ1ZSwiaXNUb2dnbGVhYmxlIjpmYWxzZSwiaXNUb2dnbGVkSGlkZGVuQnlEZWZhdWx0IjpudWxsfSx7InR5cGUiOiJjb2x1bW4iLCJuYW1lIjoiY3JlYXRlZF9hdCIsImxhYmVsIjoiQ3JlYXRlZCBhdCIsImlzSGlkZGVuIjpmYWxzZSwiaXNUb2dnbGVkIjpmYWxzZSwiaXNUb2dnbGVhYmxlIjp0cnVlLCJpc1RvZ2dsZWRIaWRkZW5CeURlZmF1bHQiOnRydWV9LHsidHlwZSI6ImNvbHVtbiIsIm5hbWUiOiJ1cGRhdGVkX2F0IiwibGFiZWwiOiJVcGRhdGVkIGF0IiwiaXNIaWRkZW4iOmZhbHNlLCJpc1RvZ2dsZWQiOmZhbHNlLCJpc1RvZ2dsZWFibGUiOnRydWUsImlzVG9nZ2xlZEhpZGRlbkJ5RGVmYXVsdCI6dHJ1ZX1dfX0=', 1782667968);
INSERT INTO `sessions` VALUES ('smnGITMP7Otobq57wGtddxDppPUIzRFHI5cwDkaJ', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/149.0.0.0 Safari/537.36', 'eyJfdG9rZW4iOiJ4VnNHbVZERTNqZE9laVdVWUs5Qm9VSXVJWGRVWkJuakNZTXJqQ3RhIiwidXJsIjpbXSwiX3ByZXZpb3VzIjp7InVybCI6Imh0dHA6XC9cLzEyNy4wLjAuMTo4MDAwXC9hcGFydG1lbnRzXC84XC9wcm9maWxlIiwicm91dGUiOiJhcGFydG1lbnRzLnByb2ZpbGUifSwiX2ZsYXNoIjp7Im9sZCI6W10sIm5ldyI6W119LCJsb2dpbl93ZWJfNTliYTM2YWRkYzJiMmY5NDAxNTgwZjAxNGM3ZjU4ZWE0ZTMwOTg5ZCI6MX0=', 1782668127);

-- ----------------------------
-- Table structure for settings
-- ----------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `group` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `locked` tinyint(1) NOT NULL DEFAULT 0,
  `payload` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `settings_group_name_unique`(`group`, `name`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of settings
-- ----------------------------

-- ----------------------------
-- Table structure for sla_events
-- ----------------------------
DROP TABLE IF EXISTS `sla_events`;
CREATE TABLE `sla_events`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `subject_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `subject_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'breach',
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'open',
  `description` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `sla_events_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `sla_events_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `sla_events_subject_type_subject_id_index`(`subject_type`, `subject_id`) USING BTREE,
  CONSTRAINT `sla_events_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `sla_events_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 19 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of sla_events
-- ----------------------------
INSERT INTO `sla_events` VALUES (1, 1, 1, NULL, NULL, 'breach', 'open', 'Phản ánh #1 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (2, 1, 1, NULL, NULL, 'breach', 'open', 'Phản ánh #2 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (3, 1, 1, NULL, NULL, 'breach', 'open', 'Phản ánh #3 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (4, 1, 1, NULL, NULL, 'breach', 'open', 'Phản ánh #4 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (5, 1, 1, NULL, NULL, 'breach', 'open', 'Phản ánh #5 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (6, 1, 1, NULL, NULL, 'breach', 'open', 'Phản ánh #6 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (7, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #7 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (8, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #8 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (9, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #9 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (10, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #10 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (11, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #11 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (12, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #12 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (13, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #13 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (14, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #14 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (15, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #15 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (16, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #16 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (17, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #17 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `sla_events` VALUES (18, 1, 1, NULL, NULL, 'due_soon', 'open', 'Phản ánh #18 sắp/đã quá hạn SLA', '2026-06-28 17:32:33', '2026-06-28 17:32:33');

-- ----------------------------
-- Table structure for statement_lines
-- ----------------------------
DROP TABLE IF EXISTS `statement_lines`;
CREATE TABLE `statement_lines`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `statement_id` bigint(20) UNSIGNED NOT NULL,
  `fee_type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `amount` decimal(16, 2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `statement_lines_statement_id_foreign`(`statement_id`) USING BTREE,
  CONSTRAINT `statement_lines_statement_id_foreign` FOREIGN KEY (`statement_id`) REFERENCES `statements` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 1 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of statement_lines
-- ----------------------------

-- ----------------------------
-- Table structure for statements
-- ----------------------------
DROP TABLE IF EXISTS `statements`;
CREATE TABLE `statements`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `billing_period_id` bigint(20) UNSIGNED NOT NULL,
  `apartment_id` bigint(20) UNSIGNED NOT NULL,
  `total_amount` decimal(16, 2) NOT NULL DEFAULT 0.00,
  `paid_amount` decimal(16, 2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'issued',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `statements_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `statements_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `statements_billing_period_id_foreign`(`billing_period_id`) USING BTREE,
  INDEX `statements_apartment_id_foreign`(`apartment_id`) USING BTREE,
  CONSTRAINT `statements_apartment_id_foreign` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `statements_billing_period_id_foreign` FOREIGN KEY (`billing_period_id`) REFERENCES `billing_periods` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `statements_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `statements_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of statements
-- ----------------------------
INSERT INTO `statements` VALUES (1, 1, 1, 7, 1, 21000000.00, 21000000.00, 'paid', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (2, 1, 1, 7, 2, 21100000.00, 21100000.00, 'paid', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (3, 1, 1, 7, 3, 21200000.00, 21200000.00, 'paid', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (4, 1, 1, 7, 4, 21300000.00, 21300000.00, 'paid', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (5, 1, 1, 7, 5, 21400000.00, 21400000.00, 'paid', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (6, 1, 1, 7, 6, 21500000.00, 21500000.00, 'paid', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (7, 1, 1, 7, 7, 21600000.00, 21600000.00, 'paid', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (8, 1, 1, 7, 8, 21700000.00, 21700000.00, 'paid', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (9, 1, 1, 7, 9, 21800000.00, 21800000.00, 'paid', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (10, 1, 1, 7, 10, 21900000.00, 8760000.00, 'partial', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (11, 1, 1, 7, 11, 22000000.00, 8800000.00, 'partial', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `statements` VALUES (12, 1, 1, 7, 12, 22100000.00, 8840000.00, 'partial', '2026-06-28 17:32:32', '2026-06-28 17:32:32');

-- ----------------------------
-- Table structure for tenants
-- ----------------------------
DROP TABLE IF EXISTS `tenants`;
CREATE TABLE `tenants`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `tenants_code_unique`(`code`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of tenants
-- ----------------------------
INSERT INTO `tenants` VALUES (1, 'T-X2-DEMO', 'X2-BMS Demo Tenant', '2026-06-28 17:32:31', '2026-06-28 17:32:31');

-- ----------------------------
-- Table structure for users
-- ----------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `building_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `is_platform_admin` tinyint(1) NOT NULL DEFAULT 0,
  `email` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `remember_token` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  UNIQUE INDEX `users_email_unique`(`email`) USING BTREE,
  INDEX `users_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `users_building_id_foreign`(`building_id`) USING BTREE,
  CONSTRAINT `users_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `users_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 2 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of users
-- ----------------------------
INSERT INTO `users` VALUES (1, 1, 1, 'Nguyễn Minh Anh', 'Trưởng BQL', 1, 'x2bms@x2bms.vn', '2026-06-28 17:32:31', '$2y$12$sXrdvVizwOPq3tukj.mFVuxDfbMfhPq6xbigs.68a6oWPgZmC3Yay', NULL, '2026-06-28 17:32:31', '2026-06-28 17:32:31');

-- ----------------------------
-- Table structure for vehicles
-- ----------------------------
DROP TABLE IF EXISTS `vehicles`;
CREATE TABLE `vehicles`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `apartment_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `resident_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `plate_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'motorbike',
  `brand` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `parking_card_no` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `monthly_fee` decimal(12, 2) NOT NULL DEFAULT 0.00,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'active',
  `valid_to` date NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `vehicles_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `vehicles_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `vehicles_apartment_id_foreign`(`apartment_id`) USING BTREE,
  INDEX `vehicles_resident_id_foreign`(`resident_id`) USING BTREE,
  CONSTRAINT `vehicles_apartment_id_foreign` FOREIGN KEY (`apartment_id`) REFERENCES `apartments` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `vehicles_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `vehicles_resident_id_foreign` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `vehicles_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 109 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of vehicles
-- ----------------------------
INSERT INTO `vehicles` VALUES (1, 1, 1, 1, 1, '30A-000.00', 'car', 'Toyota', 'PK-00001', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (2, 1, 1, 2, 2, '29-01X1.0001', 'motorbike', 'Honda', 'PK-00002', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (3, 1, 1, 3, 3, '29-02X2.0002', 'motorbike', 'Honda', 'PK-00003', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (4, 1, 1, 4, 4, '29-03X3.0003', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (5, 1, 1, 5, 5, '30A-004.04', 'car', 'Toyota', 'PK-00005', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (6, 1, 1, 6, 6, '29-05X5.0005', 'motorbike', 'Honda', 'PK-00006', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (7, 1, 1, 7, 7, '29-06X6.0006', 'motorbike', 'Honda', 'PK-00007', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (8, 1, 1, 9, 9, '30A-008.08', 'car', 'Toyota', 'PK-00009', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (9, 1, 1, 10, 10, '29-09X0.0009', 'motorbike', 'Honda', 'PK-00010', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (10, 1, 1, 11, 11, '29-10X1.0010', 'motorbike', 'Honda', 'PK-00011', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (11, 1, 1, 12, 12, '29-11X2.0011', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (12, 1, 1, 13, 13, '30A-012.12', 'car', 'Toyota', 'PK-00013', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (13, 1, 1, 14, 14, '29-13X4.0013', 'motorbike', 'Honda', 'PK-00014', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (14, 1, 1, 15, 15, '29-14X5.0014', 'motorbike', 'Honda', 'PK-00015', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (15, 1, 1, 16, 16, '29-15X6.0015', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (16, 1, 1, 17, 17, '30A-016.16', 'car', 'Toyota', 'PK-00017', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (17, 1, 1, 19, 19, '29-18X0.0018', 'motorbike', 'Honda', 'PK-00019', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (18, 1, 1, 20, 20, '29-19X1.0019', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (19, 1, 1, 21, 21, '30A-020.20', 'car', 'Toyota', 'PK-00021', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (20, 1, 1, 22, 22, '29-21X3.0021', 'motorbike', 'Honda', 'PK-00022', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (21, 1, 1, 23, 23, '29-22X4.0022', 'motorbike', 'Honda', 'PK-00023', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (22, 1, 1, 24, 24, '29-23X5.0023', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (23, 1, 1, 25, 25, '30A-024.24', 'car', 'Toyota', 'PK-00025', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (24, 1, 1, 26, 26, '29-25X7.0025', 'motorbike', 'Honda', 'PK-00026', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (25, 1, 1, 27, 27, '29-26X8.0026', 'motorbike', 'Honda', 'PK-00027', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (26, 1, 1, 29, 29, '30A-028.28', 'car', 'Toyota', 'PK-00029', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (27, 1, 1, 30, 30, '29-29X2.0029', 'motorbike', 'Honda', 'PK-00030', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (28, 1, 1, 31, 31, '29-30X3.0030', 'motorbike', 'Honda', 'PK-00031', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (29, 1, 1, 32, 32, '29-31X4.0031', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (30, 1, 1, 33, 33, '30A-032.32', 'car', 'Toyota', 'PK-00033', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (31, 1, 1, 34, 34, '29-33X6.0033', 'motorbike', 'Honda', 'PK-00034', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (32, 1, 1, 35, 35, '29-34X7.0034', 'motorbike', 'Honda', 'PK-00035', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (33, 1, 1, 36, 36, '29-35X8.0035', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (34, 1, 1, 37, 37, '30A-036.36', 'car', 'Toyota', 'PK-00037', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (35, 1, 1, 39, 39, '29-38X2.0038', 'motorbike', 'Honda', 'PK-00039', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (36, 1, 1, 40, 40, '29-39X3.0039', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (37, 1, 1, 41, 41, '30A-040.40', 'car', 'Toyota', 'PK-00041', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (38, 1, 1, 42, 42, '29-41X5.0041', 'motorbike', 'Honda', 'PK-00042', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (39, 1, 1, 43, 43, '29-42X6.0042', 'motorbike', 'Honda', 'PK-00043', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (40, 1, 1, 44, 44, '29-43X7.0043', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (41, 1, 1, 45, 45, '30A-044.44', 'car', 'Toyota', 'PK-00045', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (42, 1, 1, 46, 46, '29-45X0.0045', 'motorbike', 'Honda', 'PK-00046', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (43, 1, 1, 47, 47, '29-46X1.0046', 'motorbike', 'Honda', 'PK-00047', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (44, 1, 1, 49, 49, '30A-048.48', 'car', 'Toyota', 'PK-00049', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (45, 1, 1, 50, 50, '29-49X4.0049', 'motorbike', 'Honda', 'PK-00050', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (46, 1, 1, 51, 51, '29-50X5.0050', 'motorbike', 'Honda', 'PK-00051', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (47, 1, 1, 52, 52, '29-51X6.0051', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (48, 1, 1, 53, 53, '30A-052.52', 'car', 'Toyota', 'PK-00053', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (49, 1, 1, 54, 54, '29-53X8.0053', 'motorbike', 'Honda', 'PK-00054', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (50, 1, 1, 55, 55, '29-54X0.0054', 'motorbike', 'Honda', 'PK-00055', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (51, 1, 1, 56, 56, '29-55X1.0055', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (52, 1, 1, 57, 57, '30A-056.56', 'car', 'Toyota', 'PK-00057', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (53, 1, 1, 59, 59, '29-58X4.0058', 'motorbike', 'Honda', 'PK-00059', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (54, 1, 1, 60, 60, '29-59X5.0059', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (55, 1, 1, 61, 61, '30A-060.60', 'car', 'Toyota', 'PK-00061', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (56, 1, 1, 62, 62, '29-61X7.0061', 'motorbike', 'Honda', 'PK-00062', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (57, 1, 1, 63, 63, '29-62X8.0062', 'motorbike', 'Honda', 'PK-00063', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (58, 1, 1, 64, 64, '29-63X0.0063', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (59, 1, 1, 65, 65, '30A-064.64', 'car', 'Toyota', 'PK-00065', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (60, 1, 1, 66, 66, '29-65X2.0065', 'motorbike', 'Honda', 'PK-00066', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (61, 1, 1, 67, 67, '29-66X3.0066', 'motorbike', 'Honda', 'PK-00067', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (62, 1, 1, 69, 69, '30A-068.68', 'car', 'Toyota', 'PK-00069', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (63, 1, 1, 70, 70, '29-69X6.0069', 'motorbike', 'Honda', 'PK-00070', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (64, 1, 1, 71, 71, '29-70X7.0070', 'motorbike', 'Honda', 'PK-00071', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (65, 1, 1, 72, 72, '29-71X8.0071', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (66, 1, 1, 73, 73, '30A-072.72', 'car', 'Toyota', 'PK-00073', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (67, 1, 1, 74, 74, '29-73X1.0073', 'motorbike', 'Honda', 'PK-00074', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (68, 1, 1, 75, 75, '29-74X2.0074', 'motorbike', 'Honda', 'PK-00075', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (69, 1, 1, 76, 76, '29-75X3.0075', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (70, 1, 1, 77, 77, '30A-076.76', 'car', 'Toyota', 'PK-00077', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (71, 1, 1, 79, 79, '29-78X6.0078', 'motorbike', 'Honda', 'PK-00079', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (72, 1, 1, 80, 80, '29-79X7.0079', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (73, 1, 1, 81, 81, '30A-080.80', 'car', 'Toyota', 'PK-00081', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (74, 1, 1, 82, 82, '29-81X0.0081', 'motorbike', 'Honda', 'PK-00082', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (75, 1, 1, 83, 83, '29-82X1.0082', 'motorbike', 'Honda', 'PK-00083', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (76, 1, 1, 84, 84, '29-83X2.0083', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (77, 1, 1, 85, 85, '30A-084.84', 'car', 'Toyota', 'PK-00085', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (78, 1, 1, 86, 86, '29-85X4.0085', 'motorbike', 'Honda', 'PK-00086', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (79, 1, 1, 87, 87, '29-86X5.0086', 'motorbike', 'Honda', 'PK-00087', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (80, 1, 1, 89, 89, '30A-088.88', 'car', 'Toyota', 'PK-00089', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (81, 1, 1, 90, 90, '29-89X8.0089', 'motorbike', 'Honda', 'PK-00090', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (82, 1, 1, 91, 91, '29-90X0.0090', 'motorbike', 'Honda', 'PK-00091', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (83, 1, 1, 92, 92, '29-91X1.0091', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (84, 1, 1, 93, 93, '30A-092.92', 'car', 'Toyota', 'PK-00093', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (85, 1, 1, 94, 94, '29-93X3.0093', 'motorbike', 'Honda', 'PK-00094', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (86, 1, 1, 95, 95, '29-94X4.0094', 'motorbike', 'Honda', 'PK-00095', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (87, 1, 1, 96, 96, '29-95X5.0095', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (88, 1, 1, 97, 97, '30A-096.96', 'car', 'Toyota', 'PK-00097', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (89, 1, 1, 99, 99, '29-98X8.0098', 'motorbike', 'Honda', 'PK-00099', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (90, 1, 1, 100, 100, '29-99X0.0099', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (91, 1, 1, 101, 101, '30A-100.00', 'car', 'Toyota', 'PK-00101', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (92, 1, 1, 102, 102, '29-01X2.0101', 'motorbike', 'Honda', 'PK-00102', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (93, 1, 1, 103, 103, '29-02X3.0102', 'motorbike', 'Honda', 'PK-00103', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (94, 1, 1, 104, 104, '29-03X4.0103', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (95, 1, 1, 105, 105, '30A-104.04', 'car', 'Toyota', 'PK-00105', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (96, 1, 1, 106, 106, '29-05X6.0105', 'motorbike', 'Honda', 'PK-00106', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (97, 1, 1, 107, 107, '29-06X7.0106', 'motorbike', 'Honda', 'PK-00107', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (98, 1, 1, 109, 109, '30A-108.08', 'car', 'Toyota', 'PK-00109', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (99, 1, 1, 110, 110, '29-09X1.0109', 'motorbike', 'Honda', 'PK-00110', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (100, 1, 1, 111, 111, '29-10X2.0110', 'motorbike', 'Honda', 'PK-00111', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (101, 1, 1, 112, 112, '29-11X3.0111', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (102, 1, 1, 113, 113, '30A-112.12', 'car', 'Toyota', 'PK-00113', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (103, 1, 1, 114, 114, '29-13X5.0113', 'motorbike', 'Honda', 'PK-00114', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (104, 1, 1, 115, 115, '29-14X6.0114', 'motorbike', 'Honda', 'PK-00115', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (105, 1, 1, 116, 116, '29-15X7.0115', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (106, 1, 1, 117, 117, '30A-116.16', 'car', 'Toyota', 'PK-00117', 1200000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (107, 1, 1, 119, 119, '29-18X1.0118', 'motorbike', 'Honda', 'PK-00119', 120000.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');
INSERT INTO `vehicles` VALUES (108, 1, 1, 120, 120, '29-19X2.0119', 'bicycle', 'Giant', NULL, 0.00, 'active', '2026-12-31', '2026-06-28 17:32:32', '2026-06-28 17:32:32');

-- ----------------------------
-- Table structure for work_orders
-- ----------------------------
DROP TABLE IF EXISTS `work_orders`;
CREATE TABLE `work_orders`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint(20) UNSIGNED NOT NULL,
  `building_id` bigint(20) UNSIGNED NOT NULL,
  `department_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `feedback_request_id` bigint(20) UNSIGNED NULL DEFAULT NULL,
  `code` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `title` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'pending',
  `priority` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'normal',
  `due_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`) USING BTREE,
  INDEX `work_orders_tenant_id_foreign`(`tenant_id`) USING BTREE,
  INDEX `work_orders_building_id_foreign`(`building_id`) USING BTREE,
  INDEX `work_orders_department_id_foreign`(`department_id`) USING BTREE,
  INDEX `work_orders_feedback_request_id_foreign`(`feedback_request_id`) USING BTREE,
  CONSTRAINT `work_orders_building_id_foreign` FOREIGN KEY (`building_id`) REFERENCES `buildings` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT,
  CONSTRAINT `work_orders_department_id_foreign` FOREIGN KEY (`department_id`) REFERENCES `departments` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `work_orders_feedback_request_id_foreign` FOREIGN KEY (`feedback_request_id`) REFERENCES `feedback_requests` (`id`) ON DELETE SET NULL ON UPDATE RESTRICT,
  CONSTRAINT `work_orders_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 145 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = Dynamic;

-- ----------------------------
-- Records of work_orders
-- ----------------------------
INSERT INTO `work_orders` VALUES (1, 1, 1, 1, NULL, 'WO-0001', 'Sự cố thang máy tòa A', 'in_progress', 'high', '2026-06-29 17:32:33', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (2, 1, 1, 1, NULL, 'WO-0002', 'Thay bóng đèn hành lang tầng 3', 'pending', 'normal', '2026-06-30 17:32:33', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (3, 1, 1, 1, NULL, 'WO-0003', 'Rò rỉ nước tầng hầm B1', 'in_progress', 'high', '2026-07-01 17:32:33', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (4, 1, 1, 2, NULL, 'WO-0004', 'Kiểm tra hệ thống PCCC định kỳ', 'pending', 'high', '2026-07-02 17:32:33', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (5, 1, 1, 3, NULL, 'WO-0005', 'Vệ sinh sảnh chính', 'pending', 'normal', '2026-07-03 17:32:33', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (6, 1, 1, 2, NULL, 'WO-0006', 'Bảo trì camera tầng 5', 'in_progress', 'normal', '2026-07-04 17:32:33', '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (7, 1, 1, 1, NULL, 'WO-101', 'Công việc KT #0', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (8, 1, 1, 1, NULL, 'WO-102', 'Công việc KT #1', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (9, 1, 1, 1, NULL, 'WO-103', 'Công việc KT #2', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (10, 1, 1, 1, NULL, 'WO-104', 'Công việc KT #3', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (11, 1, 1, 1, NULL, 'WO-105', 'Công việc KT #4', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (12, 1, 1, 1, NULL, 'WO-106', 'Công việc KT #5', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (13, 1, 1, 1, NULL, 'WO-107', 'Công việc KT #6', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (14, 1, 1, 1, NULL, 'WO-108', 'Công việc KT #7', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (15, 1, 1, 1, NULL, 'WO-109', 'Công việc KT #8', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (16, 1, 1, 1, NULL, 'WO-110', 'Công việc KT #9', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (17, 1, 1, 1, NULL, 'WO-111', 'Công việc KT #10', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (18, 1, 1, 1, NULL, 'WO-112', 'Công việc KT #11', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (19, 1, 1, 1, NULL, 'WO-113', 'Công việc KT #12', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (20, 1, 1, 1, NULL, 'WO-114', 'Công việc KT #13', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (21, 1, 1, 1, NULL, 'WO-115', 'Công việc KT #14', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (22, 1, 1, 1, NULL, 'WO-116', 'Công việc KT #15', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (23, 1, 1, 1, NULL, 'WO-117', 'Công việc KT #16', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (24, 1, 1, 1, NULL, 'WO-118', 'Công việc KT #17', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (25, 1, 1, 1, NULL, 'WO-119', 'Công việc KT #18', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (26, 1, 1, 1, NULL, 'WO-120', 'Công việc KT #19', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (27, 1, 1, 1, NULL, 'WO-121', 'Công việc KT #20', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (28, 1, 1, 1, NULL, 'WO-122', 'Công việc KT #21', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (29, 1, 1, 1, NULL, 'WO-123', 'Công việc KT #22', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (30, 1, 1, 1, NULL, 'WO-124', 'Công việc KT #23', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (31, 1, 1, 1, NULL, 'WO-125', 'Công việc KT #24', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (32, 1, 1, 1, NULL, 'WO-126', 'Công việc KT #25', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (33, 1, 1, 1, NULL, 'WO-127', 'Công việc KT #26', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (34, 1, 1, 1, NULL, 'WO-128', 'Công việc KT #27', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (35, 1, 1, 1, NULL, 'WO-129', 'Công việc KT #28', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (36, 1, 1, 1, NULL, 'WO-130', 'Công việc KT #29', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (37, 1, 1, 1, NULL, 'WO-131', 'Công việc KT #30', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (38, 1, 1, 1, NULL, 'WO-132', 'Công việc KT #31', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (39, 1, 1, 1, NULL, 'WO-133', 'Công việc KT #32', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (40, 1, 1, 1, NULL, 'WO-134', 'Công việc KT #33', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (41, 1, 1, 1, NULL, 'WO-135', 'Công việc KT #34', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (42, 1, 1, 1, NULL, 'WO-136', 'Công việc KT #35', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (43, 1, 1, 1, NULL, 'WO-137', 'Công việc KT #36', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (44, 1, 1, 1, NULL, 'WO-138', 'Công việc KT #37', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (45, 1, 1, 1, NULL, 'WO-139', 'Công việc mở KT #0', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (46, 1, 1, 1, NULL, 'WO-140', 'Công việc mở KT #1', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (47, 1, 1, 1, NULL, 'WO-141', 'Công việc mở KT #2', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (48, 1, 1, 1, NULL, 'WO-142', 'Công việc mở KT #3', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (49, 1, 1, 1, NULL, 'WO-143', 'Công việc mở KT #4', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (50, 1, 1, 1, NULL, 'WO-144', 'Công việc mở KT #5', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (51, 1, 1, 2, NULL, 'WO-145', 'Công việc AN #0', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (52, 1, 1, 2, NULL, 'WO-146', 'Công việc AN #1', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (53, 1, 1, 2, NULL, 'WO-147', 'Công việc AN #2', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (54, 1, 1, 2, NULL, 'WO-148', 'Công việc AN #3', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (55, 1, 1, 2, NULL, 'WO-149', 'Công việc AN #4', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (56, 1, 1, 2, NULL, 'WO-150', 'Công việc AN #5', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (57, 1, 1, 2, NULL, 'WO-151', 'Công việc AN #6', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (58, 1, 1, 2, NULL, 'WO-152', 'Công việc AN #7', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (59, 1, 1, 2, NULL, 'WO-153', 'Công việc AN #8', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (60, 1, 1, 2, NULL, 'WO-154', 'Công việc AN #9', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (61, 1, 1, 2, NULL, 'WO-155', 'Công việc AN #10', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (62, 1, 1, 2, NULL, 'WO-156', 'Công việc AN #11', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (63, 1, 1, 2, NULL, 'WO-157', 'Công việc AN #12', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (64, 1, 1, 2, NULL, 'WO-158', 'Công việc AN #13', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (65, 1, 1, 2, NULL, 'WO-159', 'Công việc AN #14', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (66, 1, 1, 2, NULL, 'WO-160', 'Công việc AN #15', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (67, 1, 1, 2, NULL, 'WO-161', 'Công việc AN #16', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (68, 1, 1, 2, NULL, 'WO-162', 'Công việc AN #17', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (69, 1, 1, 2, NULL, 'WO-163', 'Công việc AN #18', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (70, 1, 1, 2, NULL, 'WO-164', 'Công việc AN #19', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (71, 1, 1, 2, NULL, 'WO-165', 'Công việc AN #20', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (72, 1, 1, 2, NULL, 'WO-166', 'Công việc AN #21', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (73, 1, 1, 2, NULL, 'WO-167', 'Công việc AN #22', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (74, 1, 1, 2, NULL, 'WO-168', 'Công việc AN #23', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (75, 1, 1, 2, NULL, 'WO-169', 'Công việc mở AN #0', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (76, 1, 1, 2, NULL, 'WO-170', 'Công việc mở AN #1', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (77, 1, 1, 2, NULL, 'WO-171', 'Công việc mở AN #2', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (78, 1, 1, 2, NULL, 'WO-172', 'Công việc mở AN #3', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (79, 1, 1, 3, NULL, 'WO-173', 'Công việc VS #0', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (80, 1, 1, 3, NULL, 'WO-174', 'Công việc VS #1', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (81, 1, 1, 3, NULL, 'WO-175', 'Công việc VS #2', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (82, 1, 1, 3, NULL, 'WO-176', 'Công việc VS #3', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (83, 1, 1, 3, NULL, 'WO-177', 'Công việc VS #4', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (84, 1, 1, 3, NULL, 'WO-178', 'Công việc VS #5', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (85, 1, 1, 3, NULL, 'WO-179', 'Công việc VS #6', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (86, 1, 1, 3, NULL, 'WO-180', 'Công việc VS #7', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (87, 1, 1, 3, NULL, 'WO-181', 'Công việc VS #8', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (88, 1, 1, 3, NULL, 'WO-182', 'Công việc VS #9', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (89, 1, 1, 3, NULL, 'WO-183', 'Công việc VS #10', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (90, 1, 1, 3, NULL, 'WO-184', 'Công việc VS #11', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (91, 1, 1, 3, NULL, 'WO-185', 'Công việc VS #12', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (92, 1, 1, 3, NULL, 'WO-186', 'Công việc VS #13', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (93, 1, 1, 3, NULL, 'WO-187', 'Công việc VS #14', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (94, 1, 1, 3, NULL, 'WO-188', 'Công việc VS #15', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (95, 1, 1, 3, NULL, 'WO-189', 'Công việc VS #16', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (96, 1, 1, 3, NULL, 'WO-190', 'Công việc VS #17', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (97, 1, 1, 3, NULL, 'WO-191', 'Công việc VS #18', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (98, 1, 1, 3, NULL, 'WO-192', 'Công việc VS #19', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (99, 1, 1, 3, NULL, 'WO-193', 'Công việc VS #20', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (100, 1, 1, 3, NULL, 'WO-194', 'Công việc VS #21', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (101, 1, 1, 3, NULL, 'WO-195', 'Công việc VS #22', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (102, 1, 1, 3, NULL, 'WO-196', 'Công việc VS #23', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (103, 1, 1, 3, NULL, 'WO-197', 'Công việc VS #24', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (104, 1, 1, 3, NULL, 'WO-198', 'Công việc VS #25', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (105, 1, 1, 3, NULL, 'WO-199', 'Công việc VS #26', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (106, 1, 1, 3, NULL, 'WO-200', 'Công việc VS #27', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (107, 1, 1, 3, NULL, 'WO-201', 'Công việc VS #28', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (108, 1, 1, 3, NULL, 'WO-202', 'Công việc VS #29', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (109, 1, 1, 3, NULL, 'WO-203', 'Công việc mở VS #0', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (110, 1, 1, 3, NULL, 'WO-204', 'Công việc mở VS #1', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (111, 1, 1, 4, NULL, 'WO-205', 'Công việc CS #0', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (112, 1, 1, 4, NULL, 'WO-206', 'Công việc CS #1', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (113, 1, 1, 4, NULL, 'WO-207', 'Công việc CS #2', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (114, 1, 1, 4, NULL, 'WO-208', 'Công việc CS #3', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (115, 1, 1, 4, NULL, 'WO-209', 'Công việc CS #4', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (116, 1, 1, 4, NULL, 'WO-210', 'Công việc CS #5', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (117, 1, 1, 4, NULL, 'WO-211', 'Công việc CS #6', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (118, 1, 1, 4, NULL, 'WO-212', 'Công việc CS #7', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (119, 1, 1, 4, NULL, 'WO-213', 'Công việc CS #8', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (120, 1, 1, 4, NULL, 'WO-214', 'Công việc CS #9', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (121, 1, 1, 4, NULL, 'WO-215', 'Công việc CS #10', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (122, 1, 1, 4, NULL, 'WO-216', 'Công việc CS #11', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (123, 1, 1, 4, NULL, 'WO-217', 'Công việc CS #12', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (124, 1, 1, 4, NULL, 'WO-218', 'Công việc CS #13', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (125, 1, 1, 4, NULL, 'WO-219', 'Công việc CS #14', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (126, 1, 1, 4, NULL, 'WO-220', 'Công việc CS #15', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (127, 1, 1, 4, NULL, 'WO-221', 'Công việc CS #16', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (128, 1, 1, 4, NULL, 'WO-222', 'Công việc CS #17', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (129, 1, 1, 4, NULL, 'WO-223', 'Công việc mở CS #0', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (130, 1, 1, 4, NULL, 'WO-224', 'Công việc mở CS #1', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (131, 1, 1, 4, NULL, 'WO-225', 'Công việc mở CS #2', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (132, 1, 1, 5, NULL, 'WO-226', 'Công việc TC #0', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (133, 1, 1, 5, NULL, 'WO-227', 'Công việc TC #1', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (134, 1, 1, 5, NULL, 'WO-228', 'Công việc TC #2', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (135, 1, 1, 5, NULL, 'WO-229', 'Công việc TC #3', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (136, 1, 1, 5, NULL, 'WO-230', 'Công việc TC #4', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (137, 1, 1, 5, NULL, 'WO-231', 'Công việc TC #5', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (138, 1, 1, 5, NULL, 'WO-232', 'Công việc TC #6', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (139, 1, 1, 5, NULL, 'WO-233', 'Công việc TC #7', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (140, 1, 1, 5, NULL, 'WO-234', 'Công việc TC #8', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (141, 1, 1, 5, NULL, 'WO-235', 'Công việc TC #9', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (142, 1, 1, 5, NULL, 'WO-236', 'Công việc TC #10', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (143, 1, 1, 5, NULL, 'WO-237', 'Công việc TC #11', 'done', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');
INSERT INTO `work_orders` VALUES (144, 1, 1, 5, NULL, 'WO-238', 'Công việc mở TC #0', 'pending', 'normal', NULL, '2026-06-28 17:32:33', '2026-06-28 17:32:33');

SET FOREIGN_KEY_CHECKS = 1;
