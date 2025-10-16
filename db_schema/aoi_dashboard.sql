/*
 Navicat Premium Dump SQL

 Source Server         : SERVER 204
 Source Server Type    : MySQL
 Source Server Version : 80043 (8.0.43-0ubuntu0.24.04.2)
 Source Host           : 192.168.12.204:3306
 Source Schema         : aoi_dashboard

 Target Server Type    : MySQL
 Target Server Version : 80043 (8.0.43-0ubuntu0.24.04.2)
 File Encoding         : 65001

 Date: 15/10/2025 10:46:43
*/

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- Table structure for Defects
-- ----------------------------
DROP TABLE IF EXISTS `Defects`;
CREATE TABLE `Defects`  (
  `DefectID` bigint NOT NULL AUTO_INCREMENT,
  `InspectionID` bigint NOT NULL,
  `ComponentRef` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `PartNumber` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Feature` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `MachineDefectCode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `ReworkDefectCode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `IsFalseCall` tinyint(1) NOT NULL COMMENT '1 jika False Call, 0 jika Real Defect',
  `ImageFileName` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `RootCause` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Analisis penyebab utama dari cacat.',
  `ActionTaken` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Tindakan perbaikan yang telah dilakukan.',
  PRIMARY KEY (`DefectID`) USING BTREE,
  INDEX `IX_Defects_InspectionID`(`InspectionID` ASC) USING BTREE,
  INDEX `IX_Defects_PartNumber`(`PartNumber` ASC) USING BTREE,
  INDEX `idx_defects_isfalsecall`(`IsFalseCall` ASC) USING BTREE,
  INDEX `idx_defects_inspectionid`(`InspectionID` ASC) USING BTREE,
  CONSTRAINT `Defects_ibfk_1` FOREIGN KEY (`InspectionID`) REFERENCES `Inspections` (`InspectionID`) ON DELETE CASCADE ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 275298 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for FeedbackLog
-- ----------------------------
DROP TABLE IF EXISTS `FeedbackLog`;
CREATE TABLE `FeedbackLog`  (
  `FeedbackID` bigint NOT NULL AUTO_INCREMENT,
  `DefectID` bigint NOT NULL,
  `AnalystUserID` int NOT NULL,
  `VerificationTimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `AnalystDecision` enum('Confirm False Fail','Confirm Defect','Operator Error - Defect Missed','Operator Error - Wrong Classification') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `AnalystNotes` text CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NULL,
  PRIMARY KEY (`FeedbackID`) USING BTREE,
  INDEX `AnalystUserID`(`AnalystUserID` ASC) USING BTREE,
  INDEX `fk_feedback_defect`(`DefectID` ASC) USING BTREE,
  CONSTRAINT `FeedbackLog_ibfk_2` FOREIGN KEY (`AnalystUserID`) REFERENCES `Users` (`UserID`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `fk_feedback_defect` FOREIGN KEY (`DefectID`) REFERENCES `Defects` (`DefectID`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 25 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for Inspections
-- ----------------------------
DROP TABLE IF EXISTS `Inspections`;
CREATE TABLE `Inspections`  (
  `InspectionID` bigint NOT NULL AUTO_INCREMENT,
  `LineID` int NOT NULL,
  `Assembly` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `Barcode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `LotCode` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `StartTime` datetime NULL DEFAULT NULL,
  `EndTime` datetime NULL DEFAULT NULL,
  `ReworkUser` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `InitialResult` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Hasil dari mesin (Pass/Fail)',
  `FinalResult` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL COMMENT 'Hasil akhir (Pass/Fail/False Fail)',
  `TotalLocations` int NULL DEFAULT NULL,
  `TotalDefects` int NULL DEFAULT 0,
  `TotalFalseCalls` int NULL DEFAULT 0,
  `XML_I_Path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `XML_R_Path` varchar(500) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL DEFAULT NULL,
  `RecordTimestamp` datetime NULL DEFAULT CURRENT_TIMESTAMP,
  `OperatorUserID` int NULL DEFAULT NULL,
  PRIMARY KEY (`InspectionID`) USING BTREE,
  INDEX `IX_Inspections_EndTime`(`EndTime` ASC) USING BTREE,
  INDEX `IX_Inspections_LineID`(`LineID` ASC) USING BTREE,
  INDEX `idx_inspections_endtime`(`EndTime` ASC) USING BTREE,
  INDEX `idx_inspections_line_lot_assembly`(`LineID` ASC, `LotCode` ASC, `Assembly` ASC) USING BTREE,
  INDEX `OperatorUserID`(`OperatorUserID` ASC) USING BTREE,
  CONSTRAINT `Inspections_ibfk_1` FOREIGN KEY (`LineID`) REFERENCES `ProductionLines` (`LineID`) ON DELETE RESTRICT ON UPDATE RESTRICT,
  CONSTRAINT `Inspections_ibfk_2` FOREIGN KEY (`OperatorUserID`) REFERENCES `Users` (`UserID`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 195318 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for Machines
-- ----------------------------
DROP TABLE IF EXISTS `Machines`;
CREATE TABLE `Machines`  (
  `MachineID` int NOT NULL AUTO_INCREMENT,
  `LineID` int NOT NULL,
  `MachineName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `MachineIP` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `EquipmentType` enum('Mesin','Rework') CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`MachineID`) USING BTREE,
  INDEX `fk_machines_line`(`LineID` ASC) USING BTREE,
  CONSTRAINT `fk_machines_line` FOREIGN KEY (`LineID`) REFERENCES `ProductionLines` (`LineID`) ON DELETE RESTRICT ON UPDATE RESTRICT
) ENGINE = InnoDB AUTO_INCREMENT = 13 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ProcessingLogs
-- ----------------------------
DROP TABLE IF EXISTS `ProcessingLogs`;
CREATE TABLE `ProcessingLogs`  (
  `LogID` int NOT NULL AUTO_INCREMENT,
  `Timestamp` datetime NOT NULL,
  `FilePath` varchar(512) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `Status` varchar(20) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'e.g., SUCCESS, ERROR, QUARANTINED',
  `Message` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NULL,
  PRIMARY KEY (`LogID`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 6081 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci COMMENT = 'Mencatat setiap aktivitas pemrosesan file oleh DataCollectorService.' ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for ProductionLines
-- ----------------------------
DROP TABLE IF EXISTS `ProductionLines`;
CREATE TABLE `ProductionLines`  (
  `LineID` int NOT NULL AUTO_INCREMENT,
  `LineName` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `MachineIP` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `ReworkIP` varchar(15) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  PRIMARY KEY (`LineID`) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 7 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_unicode_ci ROW_FORMAT = DYNAMIC;

-- ----------------------------
-- Table structure for Users
-- ----------------------------
DROP TABLE IF EXISTS `Users`;
CREATE TABLE `Users`  (
  `UserID` int NOT NULL AUTO_INCREMENT,
  `Username` varchar(50) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `FullName` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  `Role` enum('Operator','Analyst','Admin') CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci NOT NULL,
  PRIMARY KEY (`UserID`) USING BTREE,
  UNIQUE INDEX `Username`(`Username` ASC) USING BTREE
) ENGINE = InnoDB AUTO_INCREMENT = 9 CHARACTER SET = utf8mb4 COLLATE = utf8mb4_0900_ai_ci ROW_FORMAT = DYNAMIC;

SET FOREIGN_KEY_CHECKS = 1;
