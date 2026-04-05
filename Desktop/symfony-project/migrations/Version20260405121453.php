<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260405121453 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE assessment (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, type VARCHAR(50) DEFAULT NULL, status VARCHAR(20) DEFAULT NULL, created_at DATE DEFAULT NULL, image_path VARCHAR(500) DEFAULT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE assessment_result (id INT AUTO_INCREMENT NOT NULL, user_id INT DEFAULT NULL, total_score INT DEFAULT NULL, risk_level VARCHAR(20) DEFAULT NULL, interpretation LONGTEXT DEFAULT NULL, recommended_content LONGTEXT DEFAULT NULL, suggest_session TINYINT DEFAULT NULL, taken_at DATE DEFAULT NULL, assessment_id INT DEFAULT NULL, INDEX IDX_E7B7507DDD3DD5F1 (assessment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('CREATE TABLE question (id INT AUTO_INCREMENT NOT NULL, text LONGTEXT NOT NULL, scale VARCHAR(255) DEFAULT NULL, assessment_id INT DEFAULT NULL, INDEX IDX_B6F7494EDD3DD5F1 (assessment_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE assessment_result ADD CONSTRAINT FK_E7B7507DDD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES assessment (id)');
        $this->addSql('ALTER TABLE question ADD CONSTRAINT FK_B6F7494EDD3DD5F1 FOREIGN KEY (assessment_id) REFERENCES assessment (id)');
        $this->addSql('ALTER TABLE user CHANGE face_data face_data LONGTEXT DEFAULT NULL, CHANGE face_enabled face_enabled TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE assessment_result DROP FOREIGN KEY FK_E7B7507DDD3DD5F1');
        $this->addSql('ALTER TABLE question DROP FOREIGN KEY FK_B6F7494EDD3DD5F1');
        $this->addSql('DROP TABLE assessment');
        $this->addSql('DROP TABLE assessment_result');
        $this->addSql('DROP TABLE question');
        $this->addSql('ALTER TABLE `user` CHANGE face_data face_data TEXT DEFAULT NULL, CHANGE face_enabled face_enabled TINYINT DEFAULT 0');
    }
}
