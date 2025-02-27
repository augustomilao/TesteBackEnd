<?php

namespace Contatoseguro\TesteBackend\Service;

use Contatoseguro\TesteBackend\Config\DB;

class ProductService
{
    private \PDO $pdo;
    public function __construct()
    {
        $this->pdo = DB::connect();
    }

    public function getAll($adminUserId)
    {
        // MUDANÇA NA QUERY DO getALL para tambem mostrar todas as categorias
        $query = "
            SELECT 
                p.*, 
                GROUP_CONCAT(c.title, ', ') AS categories
            FROM 
                product p
            INNER JOIN 
                product_category pc ON pc.product_id = p.id
            INNER JOIN 
                category c ON c.id = pc.cat_id
            WHERE 
                p.company_id = {$adminUserId}
            GROUP BY 
                p.id;";

        $stm = $this->pdo->prepare($query);

        $stm->execute();

        return $stm;
    }

    public function getAllFiltered($adminUserId, $filter)
    {
        $query = "";
        switch ($filter) {
            case 1: // Ativos
                $query = "
                SELECT 
                    p.*, 
                    GROUP_CONCAT(c.title, ', ') AS categories
                FROM 
                    product p
                INNER JOIN 
                    product_category pc ON pc.product_id = p.id
                INNER JOIN 
                    category c ON c.id = pc.cat_id
                WHERE 
                    p.company_id = {$adminUserId} AND p.active = 1
                GROUP BY 
                    p.id;";
                break;
            case 2: // Desativados
                $query = "
                SELECT 
                    p.*, 
                    GROUP_CONCAT(c.title, ', ') AS categories
                FROM 
                    product p
                INNER JOIN 
                    product_category pc ON pc.product_id = p.id
                INNER JOIN 
                    category c ON c.id = pc.cat_id
                WHERE 
                    p.company_id = {$adminUserId} AND p.active = 0
                GROUP BY 
                    p.id;";
                break;
            case 3: // Data ASC
                $query = "
                SELECT 
                    p.*, 
                    GROUP_CONCAT(c.title, ', ') AS categories
                FROM 
                    product p
                INNER JOIN 
                    product_category pc ON pc.product_id = p.id
                INNER JOIN 
                    category c ON c.id = pc.cat_id
                WHERE 
                    p.company_id = {$adminUserId}
                GROUP BY 
                    p.id
                ORDER BY 
                    p.created_at ASC;";
                break;
            case 4: // Data DESC
                $query = "
                SELECT 
                    p.*, 
                    GROUP_CONCAT(c.title, ', ') AS categories
                FROM 
                    product p
                INNER JOIN 
                    product_category pc ON pc.product_id = p.id
                INNER JOIN 
                    category c ON c.id = pc.cat_id
                WHERE 
                    p.company_id = {$adminUserId}
                GROUP BY 
                    p.id
                ORDER BY 
                    p.created_at DESC;";
                break;
            default:
                // TODO: Verificar se categoria existe
                $query = "SELECT id, title FROM category";
                $stm = $this->pdo->prepare($query);
                $stm->execute();
                $filtrosExistentes = $stm->fetchAll();
                if ($filter && in_array($filter, array_column($filtrosExistentes, 'title'))) {
                    $query = "
                        SELECT 
                            p.*, 
                            GROUP_CONCAT(c.title, ', ') AS categories
                        FROM 
                            product p
                        INNER JOIN 
                            product_category pc ON pc.product_id = p.id
                        INNER JOIN 
                            category c ON c.id = pc.cat_id
                        WHERE 
                            p.company_id = {$adminUserId} and c.title = '{$filter}'
                        GROUP BY 
                            p.id;";
                } else {
                    echo "Filtro: $filter Não Existe";
                    die;
                }

                break;
        }

        $stm = $this->pdo->prepare($query);

        $stm->execute();

        return $stm;
    }


    public function getOne($id)
    {
        $stm = $this->pdo->prepare("
            SELECT *
            FROM product
            WHERE id = {$id}
        ");
        $stm->execute();

        return $stm;
    }

    public function insertOne($body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            INSERT INTO product (
                company_id,
                title,
                price,
                active
            ) VALUES (
                {$body['company_id']},
                '{$body['title']}',
                {$body['price']},
                {$body['active']}
            )
        ");
        if (!$stm->execute())
            return false;

        $productId = $this->pdo->lastInsertId();

        $stm = $this->pdo->prepare("
            INSERT INTO product_category (
                product_id,
                cat_id
            ) VALUES (
                {$productId},
                {$body['category_id']}
            );
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$productId},
                {$adminUserId},
                'create'
            )
        ");

        return $stm->execute();
    }

    public function updateOne($id, $body, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            UPDATE product
            SET company_id = {$body['company_id']},
                title = '{$body['title']}',
                price = {$body['price']},
                active = {$body['active']}
            WHERE id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            UPDATE product_category
            SET cat_id = {$body['category_id']}
            WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'update'
            )
        ");

        return $stm->execute();
    }

    public function deleteOne($id, $adminUserId)
    {
        $stm = $this->pdo->prepare("
            DELETE FROM product_category WHERE product_id = {$id}
        ");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("DELETE FROM product WHERE id = {$id}");
        if (!$stm->execute())
            return false;

        $stm = $this->pdo->prepare("
            INSERT INTO product_log (
                product_id,
                admin_user_id,
                `action`
            ) VALUES (
                {$id},
                {$adminUserId},
                'delete'
            )
        ");

        return $stm->execute();
    }

    public function getLog($id)
    {
        // TODO: (Nome do usuário, Tipo de alteração e Data)
        $stm = $this->pdo->prepare("
            SELECT a.name, p.action, p.timestamp
            FROM product_log p 
            INNER JOIN admin_user a ON a.id = p.admin_user_id
            WHERE product_id = {$id} ORDER BY p.timestamp DESC
        ");
        $stm->execute();

        return $stm;
    }

    public function getLastMod($id)
    {
        $stm = $this->pdo->prepare("
            SELECT a.name, p.action, p.timestamp
            FROM product_log p 
            INNER JOIN admin_user a ON a.id = p.admin_user_id
            WHERE product_id = {$id} ORDER BY p.timestamp DESC
            LIMIT 1
        ");
        $stm->execute();

        return $stm->fetch();
    }
}
