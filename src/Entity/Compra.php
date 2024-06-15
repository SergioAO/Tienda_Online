<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Compra
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id;

    #[ORM\ManyToOne(inversedBy: 'compras')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Pedido $idPedido = null;

    #[ORM\ManyToOne(inversedBy: 'compras')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Producto $idProducto = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $unidades = null;

    #[ORM\Column(type: 'string', length: 50, nullable: true)]
    private ?string $precio_compra = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIdPedido(): ?Pedido
    {
        return $this->idPedido;
    }

    public function setIdPedido(?Pedido $idPedido): self
    {
        $this->idPedido = $idPedido;
        return $this;
    }

    public function getUnidades(): ?int
    {
        return $this->unidades;
    }

    public function setUnidades(int $unidades): self
    {
        $this->unidades = $unidades;
        return $this;
    }

    public function getPrecio_compra(): ?string
    {
        return $this->precio_compra;
    }

    public function setPrecio_compra(string $precio_compra): self
    {
        $this->precio_compra = $precio_compra;
        return $this;
    }

    public function getIdProducto(): ?Producto
    {
        return $this->idProducto;
    }

    public function setIdProducto(Producto $idProducto): self
    {
        $this->idProducto = $idProducto;
        return $this;
    }

}