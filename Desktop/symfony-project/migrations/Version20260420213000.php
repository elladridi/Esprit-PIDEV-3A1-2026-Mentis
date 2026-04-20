<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260420213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add updated_at column to content_node for VichUploader file updates';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content_node ADD COLUMN IF NOT EXISTS updated_at DATETIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE content_node DROP COLUMN IF EXISTS updated_at');
    }
}
