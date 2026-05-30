<?php

/*
 * Contract imported from AGENTS.md
 * ## Escopo
 * - Modulo compartilhado da API.
 * - Reune infraestrutura comum: configs, device configs, uploads, imports, traducoes, listeners, filtros e servicos transversais.
 *
 * ## Quando usar
 * - Prompts sobre utilitarios compartilhados, configuracoes globais, importacao, upload, eventos comuns e funcionalidades reaproveitadas por varios modulos.
 * - Endpoints tecnicos compartilhados de runtime, como descoberta do IP visto pelo backend para o frontend web, devem nascer aqui e nao consultar banco quando o dado vier so da request.
 *
 * ## Limites
 * - Nao mover para `common` regra de negocio que pertence claramente a um dominio especifico.
 * - `common` deve servir de base para os outros modulos, nao virar dono de regras de `orders`, `financial`, `people` ou `products`.
 * - Catalogos tecnicos compartilhados usados em perfil e preferencias autenticadas, como `Timezone`, devem poder ser lidos por usuarios humanos do painel e por clientes autenticados quando o fluxo compartilhar esse cadastro.
 * - O `DefaultEventListener` precisa preservar o estado anterior real da entidade no `preUpdate`, porque o contrato de `onEntityChanged` depende desse diff.
 * - `HydratorService` define o payload padrao interno das colecoes e itens. Quando algum endpoint customizado ou decorado precisar manter filtros/paginacao da API Platform, adapte o resultado para esse payload aqui em vez de criar tolerancia no frontend.
 * - Quando a API Platform serializar colecoes Hydra do fluxo padrao, a adaptacao para `member`, `totalItems`, `search` e `view` deve acontecer nos normalizers compartilhados de `common`, sem criar controllers por recurso so para isso.
 * - Para resumos e agregacoes de colecoes, prefira o padrao de entidade com `CollectionSummary`, `CollectionSummaryProvider` e `CollectionSummaryNormalizer` em vez de criar controller customizado so para calcular `summary`.
 * - Controllers customizados so entram quando a entidade e o fluxo padrao da API Platform realmente nao cobrirem o caso; para listagens internas, a prioridade e manter o comportamento no padrao de entidade/provider/normalizer.
 * - A politica global de logs e alertas de erro deve ficar centralizada em servicos compartilhados deste modulo, usando a empresa principal como dona da configuracao publica.
 * - Captura de excecao backend, persistencia em `log`, filtros de habilitacao e retencao nao devem ser implementados em varios pontos com regras duplicadas.
 * - O cron geral de manutencao e suas rotinas tecnicas devem usar o Scheduler do Symfony e ler a agenda a partir de configuracoes da empresa principal.
 * - Chaves tecnicas de `config` como logs, rotinas e credenciais centrais devem ser filtradas no backend e nunca podem ser gravadas fora da `defaultCompany`.
 * - `config.config_key` e lido pelo frontend como chave unica da empresa. Quando existirem linhas duplicadas por `module_id`, salvar uma chave deve sincronizar todas as linhas daquela empresa/chave para evitar que uma leitura posterior recupere valor antigo.
 * - Valores de configuracao que representam listas devem substituir a lista inteira. Lista vazia significa limpar a configuracao anterior, nunca fazer merge com valores antigos.
 * - A manutencao deve remover integracoes efemeras (`Websocket` e `PushNotification`) remanescentes com mais de 24 horas; itens entregues dessas filas devem ser apagados no fluxo de entrega.
 *
 * ## Regra de extra_data
 * - `extra_data` e `extra_fields` so podem guardar chaves remotas, IDs e codigos que nao tenham coluna ou tabela materializada equivalente.
 * - Nao usar `extra_data` para snapshot de pedido, pessoa, financeiro, configuracao, logistica ou qualquer outro estado que ja caiba no dominio dono.
 * - Quando um fluxo ja tiver o destino canonico, o dado deve ir para a entidade dona e a limpeza de legado deve remover a chave correspondente de `extra_data` e, se ficar sem uso, de `extra_fields`.
 * - Writers de `extra_data` devem rejeitar `data_value` vazio, `null` ou espacos em branco; nada sem valor util pode ser persistido.
 * - Quando o writer souber a origem canonica, o campo `source` da linha deve ser preenchido com o app/dominio dono. Marketplace nao deve continuar gravando `source` nulo em bindings novos.
 *
 * ## Regras de menu
 * - O menu da home pertence ao `common` e deve ser resolvido por service/repository, nunca com query dentro de controller.
 * - Menus visiveis filtram por `menu.app_type`, `menu.enabled` e vinculos ativos de `people_link.link_type`.
 * - `ROLE_SUPER` ve todos os menus habilitados do `APP_TYPE` aberto e e o unico papel que pode configurar menus e rotas.
 * - A tela de configuracao salva os vinculos em `menu_link_type`; `menu_role` e `people_role` ficam apenas como legado.
 * - `menu_link_type` aceita somente vinculos humanos. `client`, `provider` e `franchisee` sao comerciais e nao representam perfis de menu no backend.
 *
 * ## Regras de traducao
 * - A traducao especifica da empresa selecionada deve prevalecer sobre qualquer fallback.
 * - Quando nao existir traducao propria para a empresa selecionada, a API deve considerar a traducao da empresa principal como fallback de leitura.
 * - Empresas que nao sao a principal precisam conseguir consultar a traducao da empresa principal como referencia para criar a propria sobrescrita.
 * - Somente usuarios com acesso a uma empresa podem criar ou alterar traducoes vinculadas a ela.
 * - A consulta no contexto de empresa secundaria nao deve permitir alterar a traducao da empresa principal indiretamente; a sobrescrita precisa ser gravada na propria empresa secundaria.
 */


namespace ControleOnline\Service;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class LogService
{
    private const ENTITY_CLASS_PATTERN = '/^ControleOnline\\\\Entity\\\\[A-Za-z0-9_\\\\]+$/';

    public function __construct(
        private EntityManagerInterface $manager,
        private ContainerInterface $container,
        private RequestStack $requestStack,
        private TokenStorageInterface $tokenStorage,
        private PeopleRoleService $peopleRoleService,
    ) {}

    public function securityFilter(QueryBuilder $queryBuilder, $resourceClass = null, $applyTo = null, $rootAlias = null): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $logType = strtolower(trim((string) $request?->query->get('type', '')));
        $targetClass = trim((string) $request?->query->get('class', ''));
        $targetRow = (int) preg_replace('/\D+/', '', (string) $request?->query->get('row', ''));
        $normalizedTargetClass = $this->normalizeEntityClassName($targetClass);

        if ($normalizedTargetClass && $targetRow > 0) {
            $this->applySingleEntityScope(
                $queryBuilder,
                $rootAlias,
                $normalizedTargetClass,
                $targetRow
            );

            return;
        }

        $canAccessGlobalLogs = $this->canAccessGlobalLogs();

        if ($logType === 'entity') {
            $queryBuilder->andWhere(
                $this->buildEntityCollectionAccessExpression($queryBuilder, $rootAlias)
            );
            $queryBuilder->setParameter('log_entity_type', 'entity');

            return;
        }

        if (!$canAccessGlobalLogs) {
            if ($logType !== '' && $logType !== 'all') {
                $queryBuilder->andWhere('1 = 0');

                return;
            }

            $queryBuilder->andWhere(
                $this->buildEntityCollectionAccessExpression($queryBuilder, $rootAlias)
            );
            $queryBuilder->setParameter('log_entity_type', 'entity');

            return;
        }

        if ($logType !== '' && $logType !== 'all') {
            $queryBuilder->andWhere(sprintf('%s.type = :log_type', $rootAlias));
            $queryBuilder->setParameter('log_type', $logType);

            return;
        }

        // A timeline global mistura tipos livres com logs de entidade autorizados.
        $queryBuilder->andWhere(
            $queryBuilder->expr()->orX(
                sprintf('%s.type <> :log_entity_type', $rootAlias),
                $this->buildEntityCollectionAccessExpression($queryBuilder, $rootAlias)
            )
        );
        $queryBuilder->setParameter('log_entity_type', 'entity');
    }

    private function applySingleEntityScope(
        QueryBuilder $queryBuilder,
        ?string $rootAlias,
        string $targetClass,
        int $targetRow
    ): void {
        $queryBuilder->andWhere(sprintf('%s.type = :log_type', $rootAlias));
        $queryBuilder->andWhere(sprintf('%s.class = :log_target_class', $rootAlias));
        $queryBuilder->andWhere(sprintf('%s.row = :log_target_row', $rootAlias));
        $queryBuilder->setParameter('log_type', 'entity');
        $queryBuilder->setParameter('log_target_class', $targetClass);
        $queryBuilder->setParameter('log_target_row', $targetRow);

        $service = $this->resolveEntitySecurityService($targetClass);
        if ($service === null) {
            return;
        }

        $subQueryBuilder = $this->manager->createQueryBuilder()
            ->select('log_security_entity.id')
            ->from($targetClass, 'log_security_entity')
            ->andWhere('log_security_entity.id = :log_target_row');

        $service->securityFilter(
            $subQueryBuilder,
            $targetClass,
            'collection',
            'log_security_entity'
        );

        foreach ($subQueryBuilder->getParameters() as $parameter) {
            $queryBuilder->setParameter(
                $parameter->getName(),
                $parameter->getValue(),
                $parameter->getType()
            );
        }

        $queryBuilder->andWhere(
            $queryBuilder->expr()->in(
                sprintf('%s.row', $rootAlias),
                $subQueryBuilder->getDQL()
            )
        );
    }

    private function buildEntityCollectionAccessExpression(
        QueryBuilder $queryBuilder,
        ?string $rootAlias
    ): string {
        $entityScope = $queryBuilder->expr()->orX();
        $unrestrictedClasses = [];

        foreach ($this->getDistinctEntityLogClasses() as $index => $className) {
            $securityService = $this->resolveEntitySecurityService($className);

            if ($securityService === null) {
                $unrestrictedClasses[] = $className;
                continue;
            }

            $classParameterName = sprintf('log_entity_class_%d', $index + 1);
            $subAlias = sprintf('log_security_entity_%d', $index + 1);
            $subQueryBuilder = $this->manager->createQueryBuilder()
                ->select(sprintf('%s.id', $subAlias))
                ->from($className, $subAlias);

            $securityService->securityFilter(
                $subQueryBuilder,
                $className,
                'collection',
                $subAlias
            );

            foreach ($subQueryBuilder->getParameters() as $parameter) {
                $queryBuilder->setParameter(
                    $parameter->getName(),
                    $parameter->getValue(),
                    $parameter->getType()
                );
            }

            $queryBuilder->setParameter($classParameterName, $className);
            $entityScope->add(
                $queryBuilder->expr()->andX(
                    sprintf('%s.class = :%s', $rootAlias, $classParameterName),
                    $queryBuilder->expr()->in(
                        sprintf('%s.row', $rootAlias),
                        $subQueryBuilder->getDQL()
                    )
                )
            );
        }

        if ($unrestrictedClasses) {
            $queryBuilder->setParameter(
                'log_unrestricted_entity_classes',
                array_values(array_unique($unrestrictedClasses))
            );
            $entityScope->add(
                $queryBuilder->expr()->in(
                    sprintf('%s.class', $rootAlias),
                    ':log_unrestricted_entity_classes'
                )
            );
        }

        if (!$entityScope->count()) {
            return '1 = 0';
        }

        return $queryBuilder->expr()->andX(
            sprintf('%s.type = :log_entity_type', $rootAlias),
            $entityScope
        );
    }

    private function getDistinctEntityLogClasses(): array
    {
        $classes = $this->manager->getConnection()->fetchFirstColumn(
            "SELECT DISTINCT class FROM log WHERE type = 'entity' AND class IS NOT NULL AND class <> ''"
        );

        return array_values(
            array_filter(
                array_unique(array_map('strval', $classes)),
                fn(string $className): bool => $this->normalizeEntityClassName($className) !== null
            )
        );
    }

    private function resolveEntitySecurityService(string $className): ?object
    {
        $serviceName = str_replace('Entity', 'Service', $className) . 'Service';
        if (!$this->container->has($serviceName)) {
            return null;
        }

        $service = $this->container->get($serviceName);
        if (!method_exists($service, 'securityFilter')) {
            return null;
        }

        return $service;
    }

    private function normalizeEntityClassName(string $className): ?string
    {
        $normalized = trim($className);
        if (
            $normalized === ''
            || !class_exists($normalized)
            || !preg_match(self::ENTITY_CLASS_PATTERN, $normalized)
        ) {
            return null;
        }

        return $normalized;
    }

    private function canAccessGlobalLogs(): bool
    {
        try {
            $token = $this->tokenStorage->getToken();
            $user = $token?->getUser();

            if (!is_object($user) || !method_exists($user, 'getPeople')) {
                return false;
            }

            $people = $user->getPeople();
            if (!$people) {
                return false;
            }

            return in_array(
                'super',
                $this->peopleRoleService->getAllRoles($people),
                true
            );
        } catch (\Throwable) {
            return false;
        }
    }
}
