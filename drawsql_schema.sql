CREATE TABLE `activity_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `action` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subject_type` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subject_id` bigint unsigned DEFAULT NULL,
  `metadata` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `activity_logs_user_id_subject_type_subject_id_created_at_index` (`user_id`,`subject_type`,`subject_id`,`created_at`),
  CONSTRAINT `activity_logs_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `boarding_house_facility` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `boarding_house_id` bigint unsigned NOT NULL,
  `facility_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `boarding_house_facility_boarding_house_id_facility_id_unique` (`boarding_house_id`,`facility_id`),
  KEY `boarding_house_facility_facility_id_foreign` (`facility_id`),
  CONSTRAINT `boarding_house_facility_boarding_house_id_foreign` FOREIGN KEY (`boarding_house_id`) REFERENCES `boarding_houses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boarding_house_facility_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `boarding_house_legal_documents` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `boarding_house_id` bigint unsigned NOT NULL,
  `document_type` enum('identitas_pemilik_pengelola','bukti_kepemilikan_pengelolaan','surat_kuasa_pengelolaan','izin_usaha','dokumen_lainnya') COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `document_number` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('menunggu_review','valid','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL,
  `review_note` text COLLATE utf8mb4_unicode_ci,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `boarding_house_legal_documents_reviewed_by_foreign` (`reviewed_by`),
  KEY `bhld_bh_id_doc_type_status_idx` (`boarding_house_id`,`document_type`,`status`),
  CONSTRAINT `boarding_house_legal_documents_boarding_house_id_foreign` FOREIGN KEY (`boarding_house_id`) REFERENCES `boarding_houses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boarding_house_legal_documents_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `boarding_house_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `boarding_house_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `boarding_house_photos_boarding_house_id_is_primary_index` (`boarding_house_id`,`is_primary`),
  CONSTRAINT `boarding_house_photos_boarding_house_id_foreign` FOREIGN KEY (`boarding_house_id`) REFERENCES `boarding_houses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `boarding_houses` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `owner_id` bigint unsigned NOT NULL,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `address` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `public_contact_name` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `public_contact_whatsapp_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_instructions` text COLLATE utf8mb4_unicode_ci,
  `payment_qris_image_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `payment_proof_required` tinyint(1) NOT NULL DEFAULT '1',
  `city` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `district` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `subdistrict` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `latitude` decimal(10,7) DEFAULT NULL,
  `longitude` decimal(10,7) DEFAULT NULL,
  `status` enum('draft','menunggu_verifikasi','dipublikasikan','ditolak','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL,
  `verified_at` timestamp NULL DEFAULT NULL,
  `verified_by` bigint unsigned DEFAULT NULL,
  `verification_note` text COLLATE utf8mb4_unicode_ci,
  `pending_revisions` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `boarding_houses_verified_by_foreign` (`verified_by`),
  KEY `boarding_houses_owner_id_status_index` (`owner_id`,`status`),
  KEY `boarding_houses_city_district_subdistrict_index` (`city`,`district`,`subdistrict`),
  KEY `boarding_houses_latitude_longitude_index` (`latitude`,`longitude`),
  KEY `boarding_houses_name_index` (`name`),
  CONSTRAINT `boarding_houses_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `boarding_houses_verified_by_foreign` FOREIGN KEY (`verified_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `facilities` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `type` enum('kos','kamar') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `facilities_name_type_unique` (`name`,`type`)
) ENGINE=InnoDB AUTO_INCREMENT=17 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `invoices` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenancy_id` bigint unsigned NOT NULL,
  `tenant_id` bigint unsigned NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `period_start` date NOT NULL,
  `period_end` date NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `due_date` date NOT NULL,
  `status` enum('belum_dibayar','jatuh_tempo','menunggu_konfirmasi','lunas') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `invoices_tenancy_id_period_start_period_end_unique` (`tenancy_id`,`period_start`,`period_end`),
  KEY `invoices_owner_id_foreign` (`owner_id`),
  KEY `invoices_tenant_id_owner_id_due_date_status_index` (`tenant_id`,`owner_id`,`due_date`,`status`),
  CONSTRAINT `invoices_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_tenancy_id_foreign` FOREIGN KEY (`tenancy_id`) REFERENCES `tenancies` (`id`) ON DELETE CASCADE,
  CONSTRAINT `invoices_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `payments` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `tenant_id` bigint unsigned NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `amount` decimal(12,2) NOT NULL,
  `payment_date` date NOT NULL,
  `proof_file_path` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `note` text COLLATE utf8mb4_unicode_ci,
  `status` enum('menunggu_konfirmasi','diterima','ditolak','perlu_revisi') COLLATE utf8mb4_unicode_ci NOT NULL,
  `review_note` text COLLATE utf8mb4_unicode_ci,
  `reviewed_at` timestamp NULL DEFAULT NULL,
  `reviewed_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `payments_tenant_id_foreign` (`tenant_id`),
  KEY `payments_owner_id_foreign` (`owner_id`),
  KEY `payments_reviewed_by_foreign` (`reviewed_by`),
  KEY `payments_invoice_id_tenant_id_owner_id_status_index` (`invoice_id`,`tenant_id`,`owner_id`,`status`),
  KEY `payments_invoice_id_status_index` (`invoice_id`,`status`),
  CONSTRAINT `payments_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `payments_reviewed_by_foreign` FOREIGN KEY (`reviewed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `payments_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `reports` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `reporter_id` bigint unsigned NOT NULL,
  `boarding_house_id` bigint unsigned DEFAULT NULL,
  `category` enum('data_kos_tidak_valid','kontak_tidak_valid','foto_tidak_sesuai','lainnya') COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('menunggu','diproses','selesai','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL,
  `handled_by` bigint unsigned DEFAULT NULL,
  `handled_at` timestamp NULL DEFAULT NULL,
  `resolution_note` text COLLATE utf8mb4_unicode_ci,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `reports_boarding_house_id_foreign` (`boarding_house_id`),
  KEY `reports_handled_by_foreign` (`handled_by`),
  KEY `reports_reporter_id_boarding_house_id_status_index` (`reporter_id`,`boarding_house_id`,`status`),
  CONSTRAINT `reports_boarding_house_id_foreign` FOREIGN KEY (`boarding_house_id`) REFERENCES `boarding_houses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reports_handled_by_foreign` FOREIGN KEY (`handled_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `reports_reporter_id_foreign` FOREIGN KEY (`reporter_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `room_facility` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint unsigned NOT NULL,
  `facility_id` bigint unsigned NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `room_facility_room_id_facility_id_unique` (`room_id`,`facility_id`),
  KEY `room_facility_facility_id_foreign` (`facility_id`),
  CONSTRAINT `room_facility_facility_id_foreign` FOREIGN KEY (`facility_id`) REFERENCES `facilities` (`id`) ON DELETE CASCADE,
  CONSTRAINT `room_facility_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `room_photos` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `room_id` bigint unsigned NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `caption` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `sort_order` int unsigned NOT NULL,
  `is_primary` tinyint(1) NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `room_photos_room_id_is_primary_index` (`room_id`,`is_primary`),
  CONSTRAINT `room_photos_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `rooms` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `boarding_house_id` bigint unsigned NOT NULL,
  `name` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `room_number` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `description` text COLLATE utf8mb4_unicode_ci,
  `price` decimal(12,2) NOT NULL,
  `price_period` enum('bulanan','tahunan') COLLATE utf8mb4_unicode_ci NOT NULL,
  `capacity` int unsigned NOT NULL,
  `status` enum('tersedia','terisi','tidak_aktif') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `rooms_boarding_house_id_room_number_unique` (`boarding_house_id`,`room_number`),
  KEY `rooms_boarding_house_id_status_price_index` (`boarding_house_id`,`status`,`price`),
  CONSTRAINT `rooms_boarding_house_id_foreign` FOREIGN KEY (`boarding_house_id`) REFERENCES `boarding_houses` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tenancies` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `tenant_id` bigint unsigned NOT NULL,
  `owner_id` bigint unsigned NOT NULL,
  `boarding_house_id` bigint unsigned NOT NULL,
  `room_id` bigint unsigned NOT NULL,
  `occupant_count` int unsigned NOT NULL DEFAULT '1',
  `start_date` date NOT NULL,
  `end_date` date DEFAULT NULL,
  `status` enum('aktif','selesai','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tenancies_owner_id_foreign` (`owner_id`),
  KEY `tenancies_boarding_house_id_foreign` (`boarding_house_id`),
  KEY `tenancies_tenant_owner_bh_room_status_idx` (`tenant_id`,`owner_id`,`boarding_house_id`,`room_id`,`status`),
  KEY `tenancies_room_id_status_index` (`room_id`,`status`),
  CONSTRAINT `tenancies_boarding_house_id_foreign` FOREIGN KEY (`boarding_house_id`) REFERENCES `boarding_houses` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenancies_owner_id_foreign` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenancies_room_id_foreign` FOREIGN KEY (`room_id`) REFERENCES `rooms` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tenancies_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `user_activation_tokens` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned NOT NULL,
  `token_hash` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `purpose` enum('tenant_activation') COLLATE utf8mb4_unicode_ci NOT NULL,
  `expires_at` timestamp NOT NULL,
  `used_at` timestamp NULL DEFAULT NULL,
  `created_by` bigint unsigned DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_activation_tokens_created_by_foreign` (`created_by`),
  KEY `uat_uid_purpose_expires_used_idx` (`user_id`,`purpose`,`expires_at`,`used_at`),
  CONSTRAINT `user_activation_tokens_created_by_foreign` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `user_activation_tokens_user_id_foreign` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `users` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email_verified_at` timestamp NULL DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `whatsapp_number` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `role` enum('penyewa','pemilik_kos','super_admin') COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('menunggu_aktivasi','aktif','nonaktif') COLLATE utf8mb4_unicode_ci NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT '0',
  `remember_token` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  `deleted_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `users_email_unique` (`email`),
  KEY `users_role_status_index` (`role`,`status`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `whatsapp_notifications` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `invoice_id` bigint unsigned NOT NULL,
  `tenant_id` bigint unsigned NOT NULL,
  `phone_number` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_type` enum('pengingat_jatuh_tempo','pembayaran_baru','pembayaran_dikonfirmasi') COLLATE utf8mb4_unicode_ci NOT NULL,
  `message_body` text COLLATE utf8mb4_unicode_ci,
  `scheduled_date` date NOT NULL,
  `status` enum('belum_dikirim','terkirim','gagal','tidak_dikirim') COLLATE utf8mb4_unicode_ci NOT NULL,
  `sent_at` timestamp NULL DEFAULT NULL,
  `failed_reason` text COLLATE utf8mb4_unicode_ci,
  `gateway_response` json DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `wa_notif_inv_type_date_unique` (`invoice_id`,`message_type`,`scheduled_date`),
  KEY `whatsapp_notifications_tenant_id_foreign` (`tenant_id`),
  KEY `whatsapp_notifications_invoice_id_status_sent_at_index` (`invoice_id`,`status`,`sent_at`),
  CONSTRAINT `whatsapp_notifications_invoice_id_foreign` FOREIGN KEY (`invoice_id`) REFERENCES `invoices` (`id`) ON DELETE CASCADE,
  CONSTRAINT `whatsapp_notifications_tenant_id_foreign` FOREIGN KEY (`tenant_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

