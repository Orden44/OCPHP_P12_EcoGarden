<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\ConseilRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

#[ORM\Entity(repositoryClass: ConseilRepository::class)]
class Conseil
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getConseils", "getUsers"])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(["getConseils", "getUsers"])]
    #[Assert\NotBlank(message: "Le titre du conseil est obligatoire")]
    #[Assert\Length(min: 1, max: 255, minMessage: "Le titre doit faire au moins {{ limit }} caractère", maxMessage: "Le titre ne peut pas faire plus de {{ limit }} caractères")]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Groups(["getConseils", "getUsers"])]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: "conseils")]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank(message: "La date de création du conseil est obligatoire")]
    #[Groups(["getConseils"])]
    private ?\DateTimeInterface $createdate = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    #[Groups(["getConseils"])]
    private ?\DateTimeInterface $updatedate = null;

    #[Assert\Callback("validateDates")]
    public function validateDates(ExecutionContextInterface $context)
    {
        if ($this->updatedate && $this->createdate && $this->updatedate < $this->createdate) {
            $context->buildViolation('La date de modification doit être supérieure ou égale à la date de création')
                ->addViolation();
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    public function getCreatedate(): ?\DateTimeInterface
    {
        return $this->createdate;
    }

    public function setCreatedate(\DateTimeInterface $createdate): static
    {
        $this->createdate = $createdate;

        return $this;
    }

    public function getUpdatedate(): ?\DateTimeInterface
    {
        return $this->updatedate;
    }

    public function setUpdatedate(?\DateTimeInterface $updatedate): static
    {
        $this->updatedate = $updatedate;

        return $this;
    }
}
