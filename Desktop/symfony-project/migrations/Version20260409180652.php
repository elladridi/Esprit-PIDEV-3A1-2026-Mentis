<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260409180652 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assessmentresult (result_id INT AUTO_INCREMENT NOT NULL, total_score INT DEFAULT NULL, risk_level VARCHAR(20) DEFAULT NULL, interpretation LONGTEXT DEFAULT NULL, recommended_content LONGTEXT DEFAULT NULL, suggest_session TINYINT DEFAULT NULL, taken_at DATE DEFAULT NULL, user_id INT DEFAULT NULL, assessment_id INT DEFAULT NULL, INDEX IDX_90761299A76ED395 (user_id), INDEX IDX_90761299DD3DD5F1 (assessment_id), PRIMARY KEY (result_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE event_registrations (id INT AUTO_INCREMENT NOT NULL, user_name VARCHAR(255) NOT NULL, email VARCHAR(255) NOT NULL, phone VARCHAR(50) DEFAULT NULL, ticket_type VARCHAR(50) DEFAULT \'STANDARD\' NOT NULL, number_of_tickets INT DEFAULT 1 NOT NULL, total_price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, status VARCHAR(50) DEFAULT \'CONFIRMED\' NOT NULL, payment_method VARCHAR(50) DEFAULT NULL, special_requests LONGTEXT DEFAULT NULL, registration_date DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, qr_code_path VARCHAR(255) DEFAULT NULL, confirmation_number VARCHAR(100) DEFAULT NULL, event_id INT NOT NULL, user_id INT DEFAULT NULL, UNIQUE INDEX UNIQ_7787E14B496F9884 (confirmation_number), INDEX IDX_7787E14B71F7E88B (event_id), INDEX IDX_7787E14BA76ED395 (user_id), UNIQUE INDEX unique_email_event (email, event_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE events (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, date_time DATETIME NOT NULL, location VARCHAR(255) DEFAULT NULL, max_participants INT NOT NULL, current_participants INT DEFAULT 0 NOT NULL, event_type VARCHAR(50) DEFAULT NULL, price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, image_url VARCHAR(500) DEFAULT NULL, status VARCHAR(50) DEFAULT \'UPCOMING\' NOT NULL, created_by INT DEFAULT NULL, created_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, updated_at DATETIME DEFAULT CURRENT_TIMESTAMP NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE goal (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT NOT NULL, deadline DATETIME NOT NULL, is_completed TINYINT DEFAULT 0 NOT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_FCDCEB2EA76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE mood (id INT AUTO_INCREMENT NOT NULL, feeling VARCHAR(255) NOT NULL, note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, user_id INT NOT NULL, INDEX IDX_339AEF6A76ED395 (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE session_review (review_id INT AUTO_INCREMENT NOT NULL, session_id INT NOT NULL, patient_id INT NOT NULL, rating INT NOT NULL, comment LONGTEXT DEFAULT NULL, review_date DATETIME DEFAULT NULL, PRIMARY KEY (review_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE sessions (session_id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, session_date DATE NOT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, location VARCHAR(255) NOT NULL, session_type VARCHAR(100) NOT NULL, status VARCHAR(20) DEFAULT \'scheduled\', reserved_by INT DEFAULT NULL, reserved_at DATETIME DEFAULT NULL, category VARCHAR(100) DEFAULT \'General\', popularity INT DEFAULT 0, average_rating DOUBLE PRECISION DEFAULT 0, meeting_link VARCHAR(500) DEFAULT NULL, meeting_started TINYINT DEFAULT 0, meeting_ended TINYINT DEFAULT 0, reminder_sent TINYINT DEFAULT 0, patient_confirmed TINYINT DEFAULT 0, confirmed_at DATETIME DEFAULT NULL, max_participants INT DEFAULT 20, current_participants INT DEFAULT 0, price NUMERIC(10, 2) DEFAULT \'0.00\', PRIMARY KEY (session_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE assessmentresult ADD CONSTRAINT FK_90761299A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE assessmentresult ADD CONSTRAINT FK_90761299DD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES assessment (assessment_id)');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT FK_7787E14B71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT FK_7787E14BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE goal ADD CONSTRAINT FK_FCDCEB2EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE mood ADD CONSTRAINT FK_339AEF6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE assessment_result DROP FOREIGN KEY `FK_E7B7507DDD3DD5F1`');
        $this->addSql('DROP TABLE assessment_result');
        $this->addSql('ALTER TABLE assessment MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE assessment CHANGE type type VARCHAR(100) DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL, CHANGE id assessment_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (assessment_id)');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY `FK_B6F7494EDD3DD5F1`');
        $this->addSql('ALTER TABLE question MODIFY id INT NOT NULL');
        $this->addSql('ALTER TABLE question CHANGE id question_id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (question_id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494EDD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES assessment (assessment_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assessment_result (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, total_score INT DEFAULT NULL, risk_level VARCHAR(20) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, interpretation LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, recommended_content LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, suggest_session TINYINT DEFAULT NULL, taken_at DATE DEFAULT NULL, assessment_id INT DEFAULT NULL, INDEX IDX_E7B7507DDD3DD5F1 (assessment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE assessment_result ADD CONSTRAINT `FK_E7B7507DDD3DD5F1` FOREIGN KEY (assessment_id) REFERENCES assessment (id)');
        $this->addSql('ALTER TABLE assessmentresult DROP FOREIGN KEY FK_90761299A76ED395');
        $this->addSql('ALTER TABLE assessmentresult DROP FOREIGN KEY FK_90761299DD3DD5F1');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY FK_7787E14B71F7E88B');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY FK_7787E14BA76ED395');
        $this->addSql('ALTER TABLE goal DROP FOREIGN KEY FK_FCDCEB2EA76ED395');
        $this->addSql('ALTER TABLE mood DROP FOREIGN KEY FK_339AEF6A76ED395');
        $this->addSql('DROP TABLE assessmentresult');
        $this->addSql('DROP TABLE event_registrations');
        $this->addSql('DROP TABLE events');
        $this->addSql('DROP TABLE goal');
        $this->addSql('DROP TABLE mood');
        $this->addSql('DROP TABLE session_review');
        $this->addSql('DROP TABLE sessions');
        $this->addSql('ALTER TABLE assessment MODIFY assessment_id INT NOT NULL');
        $this->addSql('ALTER TABLE assessment CHANGE type type VARCHAR(50) DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT NULL, CHANGE created_at created_at DATE DEFAULT NULL, CHANGE assessment_id id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494EDD3DD5F1');
        $this->addSql('ALTER TABLE question MODIFY question_id INT NOT NULL');
        $this->addSql('ALTER TABLE question CHANGE question_id id INT AUTO_INCREMENT NOT NULL, DROP PRIMARY KEY, ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT `FK_B6F7494EDD3DD5F1` FOREIGN KEY (assessment_id) REFERENCES assessment (id)');
    }
}
