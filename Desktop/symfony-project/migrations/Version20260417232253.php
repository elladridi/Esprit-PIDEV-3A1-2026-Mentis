<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260417232253 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE login_attempts (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(255) NOT NULL, ip_address VARCHAR(45) NOT NULL, attempted_at DATETIME NOT NULL, was_successful TINYINT NOT NULL, user_agent LONGTEXT DEFAULT NULL, country VARCHAR(50) DEFAULT NULL, city VARCHAR(50) DEFAULT NULL, INDEX idx_email (email), INDEX idx_ip (ip_address), INDEX idx_attempted_at (attempted_at), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP TABLE pending_reminders');
        $this->addSql('DROP TABLE user_old');
        $this->addSql('DROP INDEX idx_type ON assessment');
        $this->addSql('DROP INDEX idx_status ON assessment');
        $this->addSql('ALTER TABLE assessment CHANGE description description LONGTEXT DEFAULT NULL, CHANGE status status VARCHAR(50) DEFAULT NULL, CHANGE created_at created_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE assessmentresult CHANGE interpretation interpretation LONGTEXT DEFAULT NULL, CHANGE recommended_content recommended_content LONGTEXT DEFAULT NULL, CHANGE taken_at taken_at DATE DEFAULT NULL');
        $this->addSql('ALTER TABLE assessmentresult ADD CONSTRAINT FK_90761299A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE assessmentresult ADD CONSTRAINT FK_90761299DD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES assessment (assessment_id)');
        $this->addSql('DROP INDEX idx_user ON assessmentresult');
        $this->addSql('CREATE INDEX IDX_90761299A76ED395 ON assessmentresult (user_id)');
        $this->addSql('DROP INDEX idx_assessment ON assessmentresult');
        $this->addSql('CREATE INDEX IDX_90761299DD3DD5F1 ON assessmentresult (assessment_id)');
        $this->addSql('DROP INDEX idx_status ON event_registrations');
        $this->addSql('DROP INDEX idx_registration_date ON event_registrations');
        $this->addSql('ALTER TABLE event_registrations ADD qr_code_path VARCHAR(255) DEFAULT NULL, ADD confirmation_number VARCHAR(100) DEFAULT NULL, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE ticket_type ticket_type VARCHAR(50) DEFAULT \'STANDARD\' NOT NULL, CHANGE number_of_tickets number_of_tickets INT DEFAULT 1 NOT NULL, CHANGE total_price total_price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE status status VARCHAR(50) DEFAULT \'CONFIRMED\' NOT NULL, CHANGE special_requests special_requests LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT FK_7787E14B71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT FK_7787E14BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_7787E14B496F9884 ON event_registrations (confirmation_number)');
        $this->addSql('DROP INDEX idx_event_id ON event_registrations');
        $this->addSql('CREATE INDEX IDX_7787E14B71F7E88B ON event_registrations (event_id)');
        $this->addSql('DROP INDEX idx_user_id ON event_registrations');
        $this->addSql('CREATE INDEX IDX_7787E14BA76ED395 ON event_registrations (user_id)');
        $this->addSql('DROP INDEX idx_date_time ON events');
        $this->addSql('DROP INDEX idx_status ON events');
        $this->addSql('DROP INDEX created_by ON events');
        $this->addSql('DROP INDEX idx_event_type ON events');
        $this->addSql('ALTER TABLE events CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE description description LONGTEXT DEFAULT NULL, CHANGE current_participants current_participants INT DEFAULT 0 NOT NULL, CHANGE price price NUMERIC(10, 2) DEFAULT \'0.00\' NOT NULL, CHANGE status status VARCHAR(50) DEFAULT \'UPCOMING\' NOT NULL');
        $this->addSql('ALTER TABLE goal ADD title VARCHAR(255) NOT NULL, ADD is_completed TINYINT DEFAULT 0 NOT NULL, ADD created_at DATETIME NOT NULL, DROP progress, DROP status, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE deadline deadline DATETIME NOT NULL');
        $this->addSql('ALTER TABLE goal ADD CONSTRAINT FK_FCDCEB2EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('DROP INDEX idx_user ON goal');
        $this->addSql('CREATE INDEX IDX_FCDCEB2EA76ED395 ON goal (user_id)');
        $this->addSql('DROP INDEX idx_date ON mood');
        $this->addSql('ALTER TABLE mood ADD created_at DATETIME NOT NULL, DROP date, CHANGE id id INT AUTO_INCREMENT NOT NULL, CHANGE user_id user_id INT NOT NULL, CHANGE feeling feeling VARCHAR(255) NOT NULL, CHANGE note note LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE mood ADD CONSTRAINT FK_339AEF6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('DROP INDEX idx_user ON mood');
        $this->addSql('CREATE INDEX IDX_339AEF6A76ED395 ON mood (user_id)');
        $this->addSql('ALTER TABLE question CHANGE question_id question_id INT AUTO_INCREMENT NOT NULL, CHANGE text text LONGTEXT NOT NULL, CHANGE scale scale VARCHAR(255) DEFAULT NULL');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494EDD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES assessment (assessment_id)');
        $this->addSql('DROP INDEX idx_assessment ON question');
        $this->addSql('CREATE INDEX IDX_B6F7494EDD3DD5F1 ON question (assessment_id)');
        $this->addSql('DROP INDEX idx_session ON session_review');
        $this->addSql('DROP INDEX idx_patient ON session_review');
        $this->addSql('ALTER TABLE session_review CHANGE review_id review_id INT AUTO_INCREMENT NOT NULL, CHANGE comment comment LONGTEXT DEFAULT NULL, CHANGE review_date review_date DATETIME DEFAULT NULL');
        $this->addSql('DROP INDEX idx_status ON sessions');
        $this->addSql('DROP INDEX reserved_by ON sessions');
        $this->addSql('DROP INDEX idx_category ON sessions');
        $this->addSql('DROP INDEX idx_session_date ON sessions');
        $this->addSql('DROP INDEX idx_session_type ON sessions');
        $this->addSql('ALTER TABLE sessions CHANGE session_id session_id INT AUTO_INCREMENT NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'scheduled\', CHANGE average_rating average_rating DOUBLE PRECISION DEFAULT 0');
        $this->addSql('ALTER TABLE user DROP INDEX idx_email, ADD UNIQUE INDEX UNIQ_8D93D649E7927C74 (email)');
        $this->addSql('DROP INDEX idx_type ON user');
        $this->addSql('ALTER TABLE user CHANGE dateofbirth dateofbirth VARCHAR(50) NOT NULL, CHANGE face_data face_data LONGTEXT DEFAULT NULL, CHANGE face_enabled face_enabled TINYINT DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE pending_reminders (reminder_id INT NOT NULL, session_id INT NOT NULL, patient_id INT NOT NULL, weather_forecast TEXT CHARACTER SET latin1 DEFAULT NULL COLLATE `latin1_swedish_ci`, shown TINYINT DEFAULT 0, created_at DATETIME DEFAULT NULL, INDEX patient_id (patient_id), INDEX session_id (session_id), PRIMARY KEY (reminder_id)) DEFAULT CHARACTER SET latin1 COLLATE `latin1_swedish_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE user_old (id INT NOT NULL, firstname VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, lastname VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, phone VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, dateofbirth VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, email VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, password VARCHAR(1000) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('DROP TABLE login_attempts');
        $this->addSql('ALTER TABLE assessment CHANGE description description TEXT DEFAULT NULL, CHANGE status status VARCHAR(20) DEFAULT \'active\', CHANGE created_at created_at DATE DEFAULT CURRENT_DATE');
        $this->addSql('CREATE INDEX idx_type ON assessment (type)');
        $this->addSql('CREATE INDEX idx_status ON assessment (status)');
        $this->addSql('ALTER TABLE assessmentresult DROP FOREIGN KEY FK_90761299A76ED395');
        $this->addSql('ALTER TABLE assessmentresult DROP FOREIGN KEY FK_90761299DD3DD5F1');
        $this->addSql('ALTER TABLE assessmentresult DROP FOREIGN KEY FK_90761299A76ED395');
        $this->addSql('ALTER TABLE assessmentresult DROP FOREIGN KEY FK_90761299DD3DD5F1');
        $this->addSql('ALTER TABLE assessmentresult CHANGE interpretation interpretation TEXT DEFAULT NULL, CHANGE recommended_content recommended_content TEXT DEFAULT NULL, CHANGE taken_at taken_at DATE DEFAULT CURRENT_DATE');
        $this->addSql('DROP INDEX idx_90761299a76ed395 ON assessmentresult');
        $this->addSql('CREATE INDEX idx_user ON assessmentresult (user_id)');
        $this->addSql('DROP INDEX idx_90761299dd3dd5f1 ON assessmentresult');
        $this->addSql('CREATE INDEX idx_assessment ON assessmentresult (assessment_id)');
        $this->addSql('ALTER TABLE assessmentresult ADD CONSTRAINT FK_90761299A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE assessmentresult ADD CONSTRAINT FK_90761299DD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES assessment (assessment_id)');
        $this->addSql('ALTER TABLE events CHANGE id id INT NOT NULL, CHANGE description description TEXT DEFAULT NULL, CHANGE current_participants current_participants INT DEFAULT 0, CHANGE price price NUMERIC(10, 2) DEFAULT \'0.00\', CHANGE status status VARCHAR(50) DEFAULT \'UPCOMING\'');
        $this->addSql('CREATE INDEX idx_date_time ON events (date_time)');
        $this->addSql('CREATE INDEX idx_status ON events (status)');
        $this->addSql('CREATE INDEX created_by ON events (created_by)');
        $this->addSql('CREATE INDEX idx_event_type ON events (event_type)');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY FK_7787E14B71F7E88B');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY FK_7787E14BA76ED395');
        $this->addSql('DROP INDEX UNIQ_7787E14B496F9884 ON event_registrations');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY FK_7787E14B71F7E88B');
        $this->addSql('ALTER TABLE event_registrations DROP FOREIGN KEY FK_7787E14BA76ED395');
        $this->addSql('ALTER TABLE event_registrations DROP qr_code_path, DROP confirmation_number, CHANGE id id INT NOT NULL, CHANGE ticket_type ticket_type VARCHAR(50) DEFAULT \'STANDARD\', CHANGE number_of_tickets number_of_tickets INT DEFAULT 1, CHANGE total_price total_price NUMERIC(10, 2) DEFAULT \'0.00\', CHANGE status status VARCHAR(50) DEFAULT \'CONFIRMED\', CHANGE special_requests special_requests TEXT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_status ON event_registrations (status)');
        $this->addSql('CREATE INDEX idx_registration_date ON event_registrations (registration_date)');
        $this->addSql('DROP INDEX idx_7787e14b71f7e88b ON event_registrations');
        $this->addSql('CREATE INDEX idx_event_id ON event_registrations (event_id)');
        $this->addSql('DROP INDEX idx_7787e14ba76ed395 ON event_registrations');
        $this->addSql('CREATE INDEX idx_user_id ON event_registrations (user_id)');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT FK_7787E14B71F7E88B FOREIGN KEY (event_id) REFERENCES events (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE event_registrations ADD CONSTRAINT FK_7787E14BA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE goal DROP FOREIGN KEY FK_FCDCEB2EA76ED395');
        $this->addSql('ALTER TABLE goal DROP FOREIGN KEY FK_FCDCEB2EA76ED395');
        $this->addSql('ALTER TABLE goal ADD progress INT DEFAULT NULL, ADD status VARCHAR(50) DEFAULT NULL, DROP title, DROP is_completed, DROP created_at, CHANGE id id INT NOT NULL, CHANGE description description VARCHAR(255) DEFAULT NULL, CHANGE deadline deadline DATE DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('DROP INDEX idx_fcdceb2ea76ed395 ON goal');
        $this->addSql('CREATE INDEX idx_user ON goal (user_id)');
        $this->addSql('ALTER TABLE goal ADD CONSTRAINT FK_FCDCEB2EA76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE mood DROP FOREIGN KEY FK_339AEF6A76ED395');
        $this->addSql('ALTER TABLE mood DROP FOREIGN KEY FK_339AEF6A76ED395');
        $this->addSql('ALTER TABLE mood ADD date DATETIME DEFAULT NULL, DROP created_at, CHANGE id id INT NOT NULL, CHANGE feeling feeling VARCHAR(50) DEFAULT NULL, CHANGE note note TEXT DEFAULT NULL, CHANGE user_id user_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX idx_date ON mood (date)');
        $this->addSql('DROP INDEX idx_339aef6a76ed395 ON mood');
        $this->addSql('CREATE INDEX idx_user ON mood (user_id)');
        $this->addSql('ALTER TABLE mood ADD CONSTRAINT FK_339AEF6A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494EDD3DD5F1');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494EDD3DD5F1');
        $this->addSql('ALTER TABLE question CHANGE question_id question_id INT NOT NULL, CHANGE text text TEXT NOT NULL, CHANGE scale scale VARCHAR(50) DEFAULT NULL');
        $this->addSql('DROP INDEX idx_b6f7494edd3dd5f1 ON question');
        $this->addSql('CREATE INDEX idx_assessment ON question (assessment_id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494EDD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES assessment (assessment_id)');
        $this->addSql('ALTER TABLE sessions CHANGE session_id session_id INT NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'Scheduled\', CHANGE average_rating average_rating DOUBLE PRECISION DEFAULT \'0\'');
        $this->addSql('CREATE INDEX idx_status ON sessions (status)');
        $this->addSql('CREATE INDEX reserved_by ON sessions (reserved_by)');
        $this->addSql('CREATE INDEX idx_category ON sessions (category)');
        $this->addSql('CREATE INDEX idx_session_date ON sessions (session_date)');
        $this->addSql('CREATE INDEX idx_session_type ON sessions (session_type)');
        $this->addSql('ALTER TABLE session_review CHANGE review_id review_id INT NOT NULL, CHANGE comment comment TEXT DEFAULT NULL, CHANGE review_date review_date DATETIME DEFAULT CURRENT_TIMESTAMP');
        $this->addSql('CREATE INDEX idx_session ON session_review (session_id)');
        $this->addSql('CREATE INDEX idx_patient ON session_review (patient_id)');
        $this->addSql('ALTER TABLE `user` DROP INDEX UNIQ_8D93D649E7927C74, ADD INDEX idx_email (email)');
        $this->addSql('ALTER TABLE `user` CHANGE dateofbirth dateofbirth VARCHAR(100) NOT NULL, CHANGE face_data face_data TEXT DEFAULT NULL, CHANGE face_enabled face_enabled TINYINT DEFAULT 0');
        $this->addSql('CREATE INDEX idx_type ON `user` (type)');
    }
}
