<?php
/**
 * GraphController class file.
 * @author Arnaud Fabre <https://github.com/arnaud-f>
 * @copyright Copyright &copy; Arnaud Fabre 2013-
 * @license http://www.opensource.org/licenses/bsd-license.php New BSD License
 * @package auth.controllers
 */
class GraphController extends AuthController
{

    public function actionIndex($root = 'Admin', $direction = 'desc')
    {
        $this->render('rbac', array(
        	'root' => $root,
        	'direction' => $direction,
        ));
    }

    public function actionRBACJson($root = 'Admin', $direction = 'desc')
    {
		$am = Yii::app()->getAuthManager();
		$nbelements = ($direction == 'desc') ? count($am->getDescendants($root)) : count($am->getAncestors($root));
        $this->renderJSON(array(
        	'nbelements' => $nbelements,
        	'elements' => $this->buildTree($root, $direction),
        ));
    }

    private function renderJSON($data)
    {
        header("Content-Type: application/json; charset=utf-8");

        echo CJSON::encode($data);

        foreach (Yii::app()->log->routes as $route) {
            if ($route instanceof CWebLogRoute) {
                $route->enabled = false; // disable any weblogroutes
            }
        }
        Yii::app()->end();
    }

    private function buildTree($name, $direction = 'desc')
    {
        $childrenQuery = Yii::app()->db->createCommand();
        $childrenQuery = ($direction == 'desc') ? $childrenQuery->select('child') : $childrenQuery->select('parent');
        $childrenQuery = $childrenQuery->from('AuthItemChild');
        $childrenQuery = ($direction == 'desc') ?
        	$childrenQuery->where('parent=:parent', array(':parent' => $name)) :
        	$childrenQuery->where('child=:child', array(':child' => $name));

        $childrenQuery = $childrenQuery->queryColumn();

        if (empty($childrenQuery)) {
            return array(
                'name' => $name
            );
        }

        $children = array();

        foreach ($childrenQuery as $childName) {
            $children[] = $this->buildTree($childName, $direction);
        }

        return array(
            'name' => $name,
            'children' => $children
        );
    }
}
