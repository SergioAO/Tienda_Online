<?php

namespace App\Entity;

use App\Repository\UsuarioRepository;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UsuarioRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class Usuario implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $nombre = null;

    #[ORM\Column(type: 'string', length: 80, nullable: true)]
    private ?string $apellidos = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    #[Assert\Regex(pattern:"/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/", message:"La contraseña no es válida. Debe contener al menos 8 caracteres, un número, una letra mayúscula y una letra minúscula")]
    private ?string $password = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    #[Assert\Regex(pattern:"/^[\w]+\.(jpg|jpeg|png|webp)$/i", message:"Foto no válida")]
    private ?string $photo = null;

    #[ORM\OneToMany(targetEntity: Pedido::class, mappedBy: 'idUsuario', orphanRemoval: true)]
    private Collection $pedidos;

    #[ORM\OneToMany(targetEntity: Pregunta::class, mappedBy: 'usuario', orphanRemoval: true)]
    private Collection $preguntas;

    #[ORM\OneToMany(targetEntity: DatoDePago::class, mappedBy: 'usuario', orphanRemoval: true)]
    private Collection $datoDePago;
    #[ORM\OneToMany(targetEntity: NotificacionStock::class, mappedBy: 'usuario', orphanRemoval: true)]
    private Collection $notificacionesStock;
    #[ORM\OneToMany(targetEntity: Interaccion::class, mappedBy: 'usuario')]
    private Collection $interacciones;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $isVerified = 0;

    public function __construct()
    {
        $this->notificacionesStock = new ArrayCollection();
        $this->interacciones = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getNombre(): ?string
    {
        return $this->nombre;
    }

    public function setNombre(string $nombre): self
    {
        $this->nombre = $nombre;
        return $this;
    }

    public function getApellidos(): ?string
    {
        return $this->apellidos;
    }

    public function setApellidos(string $apellidos): self
    {
        $this->apellidos = $apellidos;
        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
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
    public function setRoles(array $roles): self
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

    public function setPassword(string $password): self
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

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(string $photo): self
    {
        $this->photo = $photo;
        return $this;
    }

    /**
     * @return Collection<int, Pedido>
     */
    public function getPedidos(): Collection
    {
        return $this->pedidos;
    }

    public function addPedido(Pedido $pedido): self
    {
        if (!$this->pedidos->contains($pedido)) {
            $this->pedidos->add($pedido);
            $pedido->setIdUsuario($this);
        }
        return $this;
    }

    public function removePedido(Pedido $pedido): self
    {
        if ($this->pedidos->contains($pedido)) {
            $this->pedidos->removeElement($pedido);
            if ($pedido->getIdUsuario() === $this) {
                $pedido->setIdUsuario(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Pregunta>
     */
    public function getPreguntas(): Collection
    {
        return $this->preguntas;
    }

    public function addPregunta(Pregunta $pregunta): self
    {
        if (!$this->preguntas->contains($pregunta)) {
            $this->preguntas->add($pregunta);
            $pregunta->setUsuario($this);
        }
        return $this;
    }

    public function removePregunta(Pregunta $pregunta): self
    {
        if ($this->preguntas->contains($pregunta)) {
            $this->preguntas->removeElement($pregunta);
            if ($pregunta->getUsuario() === $this) {
                $pregunta->setUsuario(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, DatoDePago>
     */
    public function getDatoDePago(): Collection
    {
        return $this->datoDePago;
    }

    public function addDatoDePago(DatoDePago $datoDePago): self
    {
        if (!$this->datoDePago->contains($datoDePago)) {
            $this->datoDePago->add($datoDePago);
            $datoDePago->setUsuario($this);
        }
        return $this;
    }

    public function removeDatoDePago(DatoDePago $datoDePago): self
    {
        if ($this->datoDePago->contains($datoDePago)) {
            $this->datoDePago->removeElement($datoDePago);
            if ($datoDePago->getUsuario() === $this) {
                $datoDePago->setUsuario(null);
            }
        }
        return $this;
    }

    public function isVerified(): int
    {
        return $this->isVerified;
    }

    public function setVerified(int $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }

    /**
     * @return Collection<int, NotificacionStock>
     */
    public function getNotificacionesStock(): Collection
    {
        return $this->notificacionesStock;
    }

    public function addNotificacionStock(NotificacionStock $notificacionStock): self
    {
        if (!$this->notificacionesStock->contains($notificacionStock)) {
            $this->notificacionesStock->add($notificacionStock);
            $notificacionStock->setUsuario($this);
        }
        return $this;
    }

    public function removeNotificacionStock(NotificacionStock $notificacionStock): self
    {
        if ($this->notificacionesStock->contains($notificacionStock)) {
            $this->notificacionesStock->removeElement($notificacionStock);
            if ($notificacionStock->getUsuario() === $this) {
                $notificacionStock->setUsuario(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, Interaccion>
     */
    public function getInteracciones(): Collection
    {
        return $this->interacciones;
    }

    public function addInteraccion(Interaccion $interaccion): self
    {
        if (!$this->interacciones->contains($interaccion)) {
            $this->interacciones->add($interaccion);
            $interaccion->setUsuario($this);
        }

        return $this;
    }

    public function removeInteraccion(Interaccion $interaccion): self
    {
        if ($this->interacciones->removeElement($interaccion)) {
            // set the owning side to null (unless already changed)
            if ($interaccion->getUsuario() === $this) {
                $interaccion->setUsuario(null);
            }
        }

        return $this;
    }
}
