<?php

namespace App\Exceptions;

use App\Models\Floor;
use App\Models\Sale;
use Brick\Math\BigDecimal;
use DomainException;

/**
 * Violação de regra de negócio do estoque. A mensagem é segura para exibir ao operador.
 */
class StockException extends DomainException
{
    public static function invalidQuantity(): self
    {
        return new self('Quantidade inválida: informe um valor maior que zero com no máximo 4 casas decimais.');
    }

    public static function zeroAdjustment(): self
    {
        return new self('O ajuste não pode ser zero.');
    }

    public static function missingField(string $label): self
    {
        return new self("O campo \"{$label}\" é obrigatório para esta operação.");
    }

    public static function emptySale(): self
    {
        return new self('A venda precisa ter ao menos um item.');
    }

    public static function floorNotFound(): self
    {
        return new self('Um ou mais pisos da operação não foram encontrados.');
    }

    public static function inactiveFloor(Floor $floor): self
    {
        return new self("O piso {$floor->code} está inativo e não pode ser movimentado.");
    }

    public static function insufficientStock(Floor $floor, BigDecimal $requested, BigDecimal $available): self
    {
        return new self(sprintf(
            'Estoque disponível insuficiente para o piso %s: solicitado %s m², disponível %s m².',
            $floor->code,
            $requested->toScale(4),
            $available->toScale(4),
        ));
    }

    public static function negativePhysical(Floor $floor): self
    {
        return new self("A operação deixaria o estoque físico do piso {$floor->code} negativo.");
    }

    public static function belowCommitted(Floor $floor): self
    {
        return new self("A operação deixaria o estoque físico do piso {$floor->code} menor que o comprometido. Trate as reservas antes.");
    }

    public static function negativeCommitted(Floor $floor): self
    {
        return new self("Inconsistência: o estoque comprometido do piso {$floor->code} ficaria negativo.");
    }

    public static function saleNotPending(Sale $sale): self
    {
        return new self("A venda {$sale->document_number} não está pendente de entrega (status atual: {$sale->status->label()}).");
    }

    public static function delivererRequired(): self
    {
        return new self('Informe o entregador para confirmar uma entrega.');
    }

    public static function delivererInvalid(): self
    {
        return new self('Entregador inválido ou inativo.');
    }
}
