<?php
/**
 * Created by PhpStorm.
 * User: simetriatecnologia
 * Date: 15/09/16
 * Time: 14:02
 */

namespace Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\Banco;

use Eduardokum\LaravelBoleto\CalculoDV;
use Eduardokum\LaravelBoleto\Cnab\Remessa\Cnab240\AbstractRemessa;
use Eduardokum\LaravelBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Eduardokum\LaravelBoleto\Contracts\Cnab\Remessa as RemessaContract;
use Eduardokum\LaravelBoleto\Util;

class Sicredi extends AbstractRemessa implements RemessaContract
{

    const OCORRENCIA_REMESSA = '01';
    const OCORRENCIA_PEDIDO_BAIXA = '02';
    const OCORRENCIA_CONCESSAO_ABATIMENTO = '04';
    const OCORRENCIA_CANC_ABATIMENTO = '05';
    const OCORRENCIA_ALT_VENCIMENTO = '06';
    const OCORRENCIA_CONCESSAO_DESCONTO = '07';
    const OCORRENCIA_CANC_DESCONTO = '08';
    const OCORRENCIA_PROTESTAR = '09';
    const OCORRENCIA_CANC_PROTESTO_BAIXAR = '10';
    const OCORRENCIA_CANC_PROTESTO = '11';
    const OCORRENCIA_ALT_MORA = '12';
    const OCORRENCIA_CANC_MORA = '13';
    const OCORRENCIA_ALT_DESCONTO = '16';
    const OCORRENCIA_NAO_CONCEDER_DESCONTO = '17';
    const OCORRENCIA_ALT_OUTROS_DADOS = '31';

    const PROTESTO_SEM = '3';
    const PROTESTO_DIAS_CORRIDOS = '1';
    const PROTESTO_NAO_PROTESTAR = '3';
    const PROTESTO_AUTOMATICO = '9';

    public function __construct(array $params)
    {
        parent::__construct($params);
        $this->setCarteira('A'); //Carteira Simples 'A'
        $this->addCampoObrigatorio('idremessa');
    }

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = BoletoContract::COD_BANCO_SICREDI;

    /**
     * Define as carteiras disponíveis para cada banco
     *
     * @var array
     */
    protected $carteiras = ['A'];

    /**
     * Codigo do cliente junto ao banco.
     *
     * @var string
     */
    protected $codigoCliente;

    /**
     * Define o código da carteira (Com ou sem registro)
     *
     * @param string $carteira
     *
     * @return AbstractRemessa
     */
    public function setCarteira($carteira)
    {
        $this->carteira = 'A';
        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function addBoleto(BoletoContract $boleto)
    {
        $this->boletos[] = $boleto;
        $this->segmentoP($boleto);
        $this->segmentoQ($boleto);
        $this->segmentoR($boleto);
        if($boleto->getSacadorAvalista()) {
            $this->segmentoY01($boleto);
        }
        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    protected function segmentoP(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'P');
        $this->add(15, 15, '');
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_OUTROS_DADOS);
        }
        $this->add(18, 22, Util::formatCnab('9', $this->getAgencia(), 5));
        $this->add(23, 23, '');
        $this->add(24, 35, Util::formatCnab('9', $this->getConta(), 12));
        $this->add(36, 36, Util::formatCnab('9', $this->getContaDv(), 1));
        $this->add(37, 37, '');
        $this->add(38, 57, Util::formatCnab('X', $boleto->getNossoNumero(), 20));
        $this->add(58, 58, '1'); //'1' = Cobrança Simples
        $this->add(59, 59, '1'); //'1' = Com cadastramento (cobrança registrada)
        $this->add(60, 60, '1'); //'1' = Tradicional
        $this->add(61, 61, '2'); //'2' = Beneficiário emite
        $this->add(62, 62, '2'); //'2' = Beneficiário distribui
        $this->add(63, 77, Util::formatCnab('X', $boleto->getNumeroDocumento(), 15));
        $this->add(78, 85, $boleto->getDataVencimento()->format('dmY'));
        $this->add(86, 100, Util::formatCnab('9', $boleto->getValor(), 15, 2));
        $this->add(101, 105, '00000');
        $this->add(106, 106, '');
        $this->add(107, 108, Util::formatCnab('9', $boleto->getEspecieDocCodigo(), 2));
        $this->add(109, 109, Util::formatCnab('9', $boleto->getAceite(), 1));
        $this->add(110, 117, $boleto->getDataDocumento()->format('dmY'));
        $this->add(118, 118, $boleto->getJuros() ? '1' : '3'); //'1' = Valor por Dia, '3' = Isento
        $this->add(119, 126, $boleto->getJurosApos() == 0 ? '00000000' :
               $boleto->getDataVencimentoApos()->format('dmY'));
        $this->add(127, 141, Util::formatCnab('9', $boleto->getMoraDia(), 15, 2)); //Valor da mora/dia ou Taxa mensal
        $this->add(142, 142, '0'); // '1' = Valor Fixo Até a Data Informada
        $this->add(143, 150, $boleto->getDesconto() > 0 ? $boleto->getDataDesconto()->format('dmY') : '00000000');
        $this->add(151, 165, Util::formatCnab('9', $boleto->getDesconto(), 15, 2));
        $this->add(166, 180, Util::formatCnab('9', 0, 15, 2));
        $this->add(181, 195, Util::formatCnab('9', 0, 15, 2));
        $this->add(196, 220, Util::formatCnab('X', $boleto->getNumeroControle(), 25));
        $this->add(221, 221, self::PROTESTO_SEM);
        if ($boleto->getDiasProtesto() > 0) {
            $this->add(221, 221, self::PROTESTO_DIAS_CORRIDOS);
        }
        $this->add(222, 223, Util::formatCnab('9', $boleto->getDiasProtesto(), 2));
        $this->add(224, 224, '1'); // '1' = Baixar / devolver - Utilizar sempre domínio ‘1’ para esse campo.
        $this->add(225, 227, '060'); // Utilizar sempre, nesse campo, 60 dias para baixa/devolução.
        $this->add(228, 229, Util::formatCnab('9', $boleto->getMoeda(), 2));
        $this->add(230, 239, '0000000000');
        $this->add(240, 240, '');

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    protected function segmentoR(BoletoContract $boleto)
    {
        if (!$boleto->getMulta() > 0 && !$boleto->getDesconto() > 0) {
            return $this;
        }

        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'R');
        $this->add(15, 15, '');
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_OUTROS_DADOS);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO_DATA) {
            $this->add(16, 17, self::OCORRENCIA_ALT_VENCIMENTO);
        }
        if ($boleto->getStatus() == $boleto::STATUS_CUSTOM) {
            $this->add(16, 17, sprintf('%2.02s', $boleto->getComando()));
        }

        $this->add(18, 18, '0');
        $this->add(19, 26, Util::formatCnab('9', 0, 8, 2));
        $this->add(27, 41, Util::formatCnab('9', 0, 15, 2));

        if ($boleto->getDesconto() > 0) {
            $this->add(18, 18, '1'); // '1' = Valor fixo até a data informada
            $this->add(19, 26, $boleto->getDataDesconto() ? $boleto->getDataDesconto()->format('dmY') : $boleto->getDataVencimento()->format('dmY'));
            $this->add(27, 41, Util::formatCnab('9', $boleto->getMulta(), 13, 2));
        }

        $this->add(42, 42, '0');
        $this->add(43, 50, Util::formatCnab('9', '0', 8));
        $this->add(51, 65, Util::formatCnab('9', '0', 15));
        $this->add(66, 66, '0');
        $this->add(67, 74, '00000000');
        $this->add(75, 89, Util::formatCnab('9', 0, 15, 2));
        if ($boleto->getMulta() > 0) {
            $percentualMulta = number_format(($boleto->getMulta() * 100), 2);
            $this->add(66, 66, '2'); // '2' = Percentual
            $this->add(67, 74, $boleto->getDataVencimento()->format('dmY'));
            $this->add(75, 89, Util::formatCnab('9', $percentualMulta, 15, 2));
        }
        $this->add(90, 99, Util::formatCnab('X', '', 10));
        $this->add(100, 139, Util::formatCnab('X', '', 40));
        $this->add(140, 179, Util::formatCnab('X', '', 40));
        $this->add(180, 199, Util::formatCnab('X', '', 20));
        $this->add(200, 207, Util::formatCnab('9', 0, 8));
        $this->add(208, 210, Util::formatCnab('9', Util::onlyNumbers($this->getCodigoBanco()), 3));
        $this->add(211, 215, Util::formatCnab('9', $this->getAgencia(), 5));
        $this->add(216, 216, '');
        $this->add(217, 228, Util::formatCnab('9', $this->getConta(), 12));
        $this->add(229, 229, Util::modulo11($this->getConta()));
        $this->add(230, 230, '');
        $this->add(231, 231, '');
        $this->add(232, 240, Util::formatCnab('X', '', 9));

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function segmentoQ(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'Q');
        $this->add(15, 15, '');
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_OUTROS_DADOS);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO_DATA) {
            $this->add(16, 17, self::OCORRENCIA_ALT_VENCIMENTO);
        }
        if ($boleto->getStatus() == $boleto::STATUS_CUSTOM) {
            $this->add(16, 17, sprintf('%2.02s', $boleto->getComando()));
        }
        $this->add(18, 18, strlen(Util::onlyNumbers($boleto->getPagador()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 33, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getDocumento()), 15));
        $this->add(34, 73, Util::formatCnab('X', $boleto->getPagador()->getNome(), 40));
        $this->add(74, 113, Util::formatCnab('X', $boleto->getPagador()->getEndereco(), 40));
        $this->add(114, 128, Util::formatCnab('X', $boleto->getPagador()->getBairro(), 15));
        $this->add(129, 133, Util::formatCnab('9', Util::onlyNumbers($boleto->getPagador()->getCep()), 5));
        $this->add(134, 136, Util::formatCnab('9', Util::onlyNumbers(substr($boleto->getPagador()->getCep(), 6, 9)), 3));
        $this->add(137, 151, Util::formatCnab('X', $boleto->getPagador()->getCidade(), 15));
        $this->add(152, 153, Util::formatCnab('X', $boleto->getPagador()->getUf(), 2));
        $this->add(154, 154, '0');
        $this->add(155, 169, '000000000000000');
        $this->add(170, 209, '');
        $this->add(210, 212, '000');
        $this->add(213, 232, '');
        $this->add(233, 240, '');

        if($boleto->getSacadorAvalista()) {
            $this->add(154, 154, strlen(Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento())) == 14 ? 2 : 1);
            $this->add(155, 169, Util::formatCnab('9', Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento()), 15));
            $this->add(170, 209, Util::formatCnab('X', $boleto->getSacadorAvalista()->getNome(), 30));
        }

        return $this;
    }

    /**
     * @param BoletoContract $boleto
     *
     * @return $this
     * @throws \Exception
     */
    public function segmentoY01(BoletoContract $boleto)
    {
        $this->iniciaDetalhe();
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '3');
        $this->add(9, 13, Util::formatCnab('9', $this->iRegistrosLote, 5));
        $this->add(14, 14, 'Y');
        $this->add(15, 15, '');
        $this->add(16, 17, self::OCORRENCIA_REMESSA);
        if ($boleto->getStatus() == $boleto::STATUS_BAIXA) {
            $this->add(16, 17, self::OCORRENCIA_PEDIDO_BAIXA);
        }
        if ($boleto->getStatus() == $boleto::STATUS_ALTERACAO) {
            $this->add(16, 17, self::OCORRENCIA_ALT_OUTROS_DADOS);
        }
        $this->add(18, 19, '01');
        $this->add(20, 20, strlen(Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento())) == 14 ? 2 : 1);
        $this->add(21, 35, Util::formatCnab('9', Util::onlyNumbers($boleto->getSacadorAvalista()->getDocumento()), 15));
        $this->add(36, 75, Util::formatCnab('X', $boleto->getSacadorAvalista()->getNome(), 40));
        $this->add(76, 115, Util::formatCnab('X', $boleto->getSacadorAvalista()->getEndereco(), 40));
        $this->add(116, 130, Util::formatCnab('X', $boleto->getSacadorAvalista()->getBairro(), 15));
        $this->add(131, 138, Util::formatCnab('9', Util::onlyNumbers($boleto->getSacadorAvalista()->getCep()), 8));
        $this->add(139, 153, Util::formatCnab('X', $boleto->getSacadorAvalista()->getCidade(), 15));
        $this->add(154, 155, Util::formatCnab('X', $boleto->getSacadorAvalista()->getUf(), 2));
        $this->add(156, 240, '');

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function header()
    {
        $this->iniciaHeader();

        /**
         * HEADER DE ARQUIVO
         */
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0000');
        $this->add(8, 8, '0');
        $this->add(9, 17, '');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 32, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 14));
        $this->add(33, 52, Util::formatCnab('X', '', 20));
        $this->add(53, 57, Util::formatCnab('9', $this->getAgencia(), 5));
        $this->add(58, 58, '');
        $this->add(59, 70, Util::formatCnab('9', $this->getConta(), 12));
        $this->add(71, 71, Util::formatCnab('9', $this->getContaDv(), 1));
        $this->add(72, 72, '');
        $this->add(73, 102, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(103, 132, Util::formatCnab('X', 'SICREDI', 30));
        $this->add(133, 142, '');
        $this->add(143, 143, 1);
        $this->add(144, 151, $this->getDataRemessa('dmY'));
        $this->add(152, 157, date('His'));
        $this->add(158, 163, Util::formatCnab('9', $this->getIdremessa(), 6));
        $this->add(164, 166, '081');
        $this->add(167, 171, '01600');
        $this->add(172, 191, '');
        $this->add(192, 211, '');
        $this->add(212, 240, '');


        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function headerLote()
    {
        $this->iniciaHeaderLote();

        /**
         * HEADER DE LOTE
         */
        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '1');
        $this->add(9, 9, 'R');
        $this->add(10, 11, '01');
        $this->add(12, 13, '');
        $this->add(14, 16, '040');
        $this->add(17, 17, '');
        $this->add(18, 18, strlen(Util::onlyNumbers($this->getBeneficiario()->getDocumento())) == 14 ? 2 : 1);
        $this->add(19, 33, Util::formatCnab('9', Util::onlyNumbers($this->getBeneficiario()->getDocumento()), 15));
        $this->add(34, 53, Util::formatCnab('X', '', 20));
        $this->add(54, 58, Util::formatCnab('9', $this->getAgencia(), 5));
        $this->add(59, 59, '');
        $this->add(60, 71, Util::formatCnab('9', $this->getConta(), 12));
        $this->add(72, 72, Util::formatCnab('9', $this->getContaDv(), 1));
        $this->add(73, 73, '');
        $this->add(74, 103, Util::formatCnab('X', $this->getBeneficiario()->getNome(), 30));
        $this->add(104, 183, '');
        $this->add(184, 191, Util::formatCnab('9', $this->getIdremessa(), 8));
        $this->add(192, 199, date('dmY'));
        $this->add(200, 207, '00000000');
        $this->add(208, 240, '');

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function trailerLote()
    {
        $this->iniciaTrailerLote();

        $valor = array_reduce($this->boletos, function($valor, $boleto) {
            return $valor + $boleto->getValor();
        }, 0);

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '0001');
        $this->add(8, 8, '5');
        $this->add(9, 17, '');
        $this->add(18, 23, Util::formatCnab('9', $this->getCountDetalhes() + 2, 6));
        $this->add(24, 29, Util::formatCnab('9', 0, 6));
        $this->add(30, 46, Util::formatCnab('9', 0, 17, 2));
        $this->add(47, 52, Util::formatCnab('9', 0, 6));
        $this->add(53, 69, Util::formatCnab('9', 0, 17, 2));
        $this->add(70, 75, Util::formatCnab('9', 0, 6));
        $this->add(76, 92, Util::formatCnab('9', 0, 17, 2));
        $this->add(93, 98, Util::formatCnab('9', 0, 6));
        $this->add(99, 115, Util::formatCnab('9', 0, 17, 2));
        $this->add(116, 123, '00000000');
        $this->add(124, 240, '');

        return $this;
    }

    /**
     * @return $this
     * @throws \Exception
     */
    protected function trailer()
    {
        $this->iniciaTrailer();

        $this->add(1, 3, Util::onlyNumbers($this->getCodigoBanco()));
        $this->add(4, 7, '9999');
        $this->add(8, 8, '9');
        $this->add(9, 17, '');
        $this->add(18, 23, Util::formatCnab('9', 1, 6));
        $this->add(24, 29, Util::formatCnab('9', $this->getCount(), 6));
        $this->add(30, 35, '000000');
        $this->add(36, 240, '');

        return $this;
    }
}
