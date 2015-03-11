<?php
/**
 * Role: danielwolf
 * Date: 3/5/15
 * Time: 3:56 PM
 */

namespace APICMS\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Silex\Application;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;
use APICMS\Controller\AbstractEntityController as BaseController;

class RoleController extends BaseController
{
    const SELECT_STATEMENT = 'r.id, r.name, DATE_FORMAT(r.created, "%Y-%m-%dT%TZ") as created';

    /**
     * {@inheritdoc}
     */
    public function get(Application $app, Request $request, $roleId)
    {
        //todo check permissions
        $query = $app['db']->createQueryBuilder()
            ->select(self::SELECT_STATEMENT)
            ->from('roles', 'r')
            ->where('r.id = :role_id')
            ->setParameter('role_id', $roleId);

        $role = $app['db']->fetchAssoc(
            $query->getSQL(),
            $query->getParameters()
        );

        if (!$role) {
            return $this->jsonResponse(BaseController::ERR_STATUS, [
                'message' => BaseController::NOT_FOUND_MSG
            ], 404);
        }

        return $this->jsonResponse(BaseController::OK_STATUS, $role, 200);
    }

    /**
     * {@inheritdoc}
     */
    public function put(Application $app, Request $request, $roleId)
    {
        $input = $request->request->all();

        // validation
        $inputConstraints = new Assert\Collection([
            'name' => new Assert\Optional(new Assert\NotBlank())
        ]);
        $errors = $app['validator']->validateValue($input, $inputConstraints);

        if (count($errors) > 0) {
            $errorArray = [];
            foreach ($errors as $e) {
                $errorArray[$e->getPropertyPath()] = $e->getMessage();
            }
            return $this->jsonResponse(BaseController::ERR_STATUS, $errorArray, 400);
        }

        // create role
        try {
            $app['db']->update('roles', $input, ['id' => $roleId]);
        } catch (UniqueConstraintViolationException $e) {
            return $this->jsonResponse(BaseController::ERR_STATUS, [
                '[name]' => 'This role has already been created.'
            ], 400);
        }

        $roleQuery = $app['db']->createQueryBuilder()
            ->select(self::SELECT_STATEMENT)
            ->from('roles', 'r')
            ->where('r.id = :id')
            ->setParameter('id', $roleId);
        $role = $app['db']->fetchAssoc($roleQuery->getSQL(), $roleQuery->getParameters());

        return $this->jsonResponse(self::OK_STATUS, $role, 201);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Application $app, Request $request, $roleId)
    {
        //todo check perms
        $affectedRows = $app['db']->delete('roles', ['id' => $roleId]);
        if ($affectedRows) {
            return $this->jsonResponse(BaseController::OK_STATUS, ['message' => 'Role deleted successfully.'], 200);
        } else {
            return $this->jsonResponse(BaseController::ERR_STATUS, ['message' => BaseController::NOT_FOUND_MSG], 404);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function post(Application $app, Request $request)
    {
        $input = $request->request->all();

        // validation
        $inputConstraints = new Assert\Collection([
            'name' => new Assert\NotBlank()
        ]);
        $errors = $app['validator']->validateValue($input, $inputConstraints);

        if (count($errors) > 0) {
            $errorArray = [];
            foreach ($errors as $e) {
                $errorArray[$e->getPropertyPath()] = $e->getMessage();
            }
            return $this->jsonResponse(BaseController::ERR_STATUS, $errorArray, 400);
        }

        // create role
        try {
            $app['db']->insert('roles', $input);
        } catch (UniqueConstraintViolationException $e) {
            return $this->jsonResponse(BaseController::ERR_STATUS, [
                '[name]' => 'This role has already been created.'
            ], 400);
        }

        $roleQuery = $app['db']->createQueryBuilder()
            ->select(self::SELECT_STATEMENT)
            ->from('roles', 'r')
            ->where('r.id = :id')
            ->setParameter('id', $app['db']->lastInsertId());
        $role = $app['db']->fetchAssoc($roleQuery->getSQL(), $roleQuery->getParameters());

        return $this->jsonResponse(self::OK_STATUS, $role, 201);
    }

    /**
     * {@inheritdoc}
     */
    public function getList(Application $app, Request $request)
    {
        // todo check perms
        $query = $app['db']->createQueryBuilder()
            ->select(self::SELECT_STATEMENT)
            ->from('roles', 'r');
        $results = $app['db']->fetchAll($query->getSQL(), $query->getParameters());
        return $this->jsonResponse(BaseController::OK_STATUS, $results, 200);
    }
}