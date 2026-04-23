<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuestionRepository::class)]
#[ORM\Table(name: 'question')]
class Question
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(name: 'question_id', type: 'integer')]
    private ?int $questionId = null;

    #[ORM\Column(name: 'text', type: 'text')]
    private ?string $text = null;

    #[ORM\Column(name: 'scale', type: 'string', length: 255, nullable: true)]
    private ?string $scale = null;

    #[ORM\ManyToOne(targetEntity: Assessment::class, inversedBy: 'questions')]
    #[ORM\JoinColumn(name: 'assessment_id', referencedColumnName: 'assessment_id')]
    private ?Assessment $assessment = null;

    public function getQuestionId(): ?int
    {
        return $this->questionId;
    }

    public function setQuestionId(int $questionId): self
    {
        $this->questionId = $questionId;
        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getScale(): ?string
    {
        return $this->scale;
    }

    public function setScale(?string $scale): self
    {
        $this->scale = $scale;
        return $this;
    }

    public function getAssessment(): ?Assessment
    {
        return $this->assessment;
    }

    public function setAssessment(?Assessment $assessment): self
    {
        $this->assessment = $assessment;
        return $this;
    }
}