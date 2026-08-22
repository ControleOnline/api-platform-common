<?php

namespace ControleOnline\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class PublicCategoryProjectionGuard
{
    private const CLIENT_PROJECTION_PARAMETERS = ['categoryIds', 'ids'];

    public function rejectClientProjection(Request $request): void
    {
        foreach (self::CLIENT_PROJECTION_PARAMETERS as $parameter) {
            if ($request->query->has($parameter)) {
                throw new BadRequestHttpException(sprintf(
                    'The "%s" parameter cannot define the published category projection.',
                    $parameter
                ));
            }
        }
    }
}
