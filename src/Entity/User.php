<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_USERNAME', fields: ['username'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(["getConseils", "getUsers"])]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    #[Assert\NotBlank(message: "Un nom d'utilisateur est obligatoire")]  
    #[Assert\Length(min: 1, max: 180, minMessage: "Le nom d'utilisateur doit faire au moins {{ limit }} caractère", maxMessage: "Le nom d'utilisateur ne peut pas faire plus de {{ limit }} caractères")]  
    #[Groups(["getConseils", "getUsers"])]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    #[Groups(["getUsers"])]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\NotBlank(message: "Le mot de passe de l'utilisateur est obligatoire")]  
    #[Assert\Length(min: 1, max: 255, minMessage: "Le mot de passe doit faire au moins {{ limit }} caractère", maxMessage: "Le mot de passe ne peut pas faire plus de {{ limit }} caractères")]    
    #[Groups(["getUsers"])]
    private ?string $password = null;

    /**
     * @var Collection<int, Conseil>
     */
    #[ORM\OneToMany(targetEntity: Conseil::class, mappedBy: 'user', orphanRemoval: true)]
    #[Groups(["getConseils", "getUsers"])]
    private Collection $conseils;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: "La ville de l'utilisateur est obligatoire")]    
    #[Assert\Length(min: 1, max: 255, minMessage: "Le nom de ville doit faire au moins {{ limit }} caractère", maxMessage: "Le nom de ville ne peut pas faire plus de {{ limit }} caractères")]  
    #[Groups(["getUsers"])]
    private ?string $city = null;

    public function __construct()
    {
        $this->conseils = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->username;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    /**
     * @return Collection<int, Conseil>
     */
    public function getConseils(): Collection
    {
        return $this->conseils;
    }

    public function addConseil(Conseil $conseil): static
    {
        if (!$this->conseils->contains($conseil)) {
            $this->conseils->add($conseil);
            $conseil->setUser($this);
        }

        return $this;
    }

    public function removeConseil(Conseil $conseil): static
    {
        if ($this->conseils->removeElement($conseil)) {
            // set the owning side to null (unless already changed)
            if ($conseil->getUser() === $this) {
                $conseil->setUser(null);
            }
        }

        return $this;
    }

    public function getCity(): ?string
    {
        return $this->city;
    }

    public function setCity(string $city): static
    {
        $this->city = $city;

        return $this;
    }

    public function isValidCredentials(string $username, string $password): bool
    {
        // Vérifier les informations d'identification de l'utilisateur
        return $this->username === $username && password_verify($password, $this->password);
    }
}
