-- Fix plain-text passwords on shared hosting (login uses password_verify → bcrypt required).
-- Run this in phpMyAdmin on your CampusSafe database after selecting the correct database.
-- Plain-text equivalents unchanged: UPSA001=2001-05-14, UPSA002=2002-09-22, UPSA003=2000-12-03,
-- STAFF*=staff123, HOST001=hostel123, ADMIN001=admin123

UPDATE users SET password = '$2y$10$tJTPqeZOLdexMoTVexXyb.X4/naKEICfdjH5VhFCCjwKvUReWLiBW' WHERE user_id = 'UPSA001';
UPDATE users SET password = '$2y$10$qBoCTq.tJq1KuTZhtY9QYeZpBGgSER/BK6dtRIT4EKPva5Pqx/fVW' WHERE user_id = 'UPSA002';
UPDATE users SET password = '$2y$10$XM0EtVPAkiEUjfC0qrFHhe8GyOrr.hWACjDLvpa2fL8WQ9JqFeEkG' WHERE user_id = 'UPSA003';
UPDATE users SET password = '$2y$10$y4uxUrGhWkegRLJq4c3Rg.L5qjZdKLZLuWXgzmyq4fFwwo0FOrGIm' WHERE user_id = 'STAFF001';
UPDATE users SET password = '$2y$10$y4uxUrGhWkegRLJq4c3Rg.L5qjZdKLZLuWXgzmyq4fFwwo0FOrGIm' WHERE user_id = 'STAFF002';
UPDATE users SET password = '$2y$10$y4uxUrGhWkegRLJq4c3Rg.L5qjZdKLZLuWXgzmyq4fFwwo0FOrGIm' WHERE user_id = 'STAFF003';
UPDATE users SET password = '$2y$10$VeYwQhiidjsDKsLePQ.gE.0rQQWKHze9Z9Gc/00yaMFz/Yu.hoyeq' WHERE user_id = 'HOST001';
UPDATE users SET password = '$2y$10$/piBFrpnj6GhMgVy07/Xiu7dhVPrAOpqjyhhUT7FoYktJC9P85BEG' WHERE user_id = 'ADMIN001';
