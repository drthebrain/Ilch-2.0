<?php
/**
 * @copyright Ilch 2.0
 * @package ilch
 */

namespace Modules\Article\Mappers;

use Modules\Article\Models\Category as CategoryModel;

class Category extends \Ilch\Mapper
{
    /**
     * Gets categorys.
     *
     * @param array $where
     * @return CategoryModel[]|null
     */
    public function getCategories($where = [])
    {
        $categoryArray = $this->db()->select('*')
            ->from('articles_cats')
            ->where($where)
            ->order(['id' => 'ASC'])
            ->execute()
            ->fetchRows();

        if (empty($categoryArray)) {
            return null;
        }

        $categorys = [];

        foreach ($categoryArray as $categoryRow) {
            $categoryModel = new CategoryModel();
            $categoryModel->setId($categoryRow['id']);
            $categoryModel->setName($categoryRow['name']);
            $categorys[] = $categoryModel;
        }

        return $categorys;
    }

    /**
     * Returns category found by the id.
     *
     * @param int $id
     * @return false|CategoryModel
     */
    public function getCategoryById($id)
    {
        $cats = $this->getCategories(['id' => $id]);
        return reset($cats);
    }

    /**
     * Inserts or updates category model.
     *
     * @param CategoryModel $category
     */
    public function save(CategoryModel $category)
    {
        $fields =
            [
            'name' => $category->getName()
            ];

        if ($category->getId()) {
            $this->db()->update('articles_cats')
                ->values($fields)
                ->where(['id' => $category->getId()])
                ->execute();
        } else {
            $this->db()->insert('articles_cats')
                ->values($fields)
                ->execute();
        }
    }

    /**
     * Deletes category with given id.
     *
     * @param integer $id
     */
    public function delete($id)
    {
        $this->db()->delete('articles_cats')
            ->where(['id' => $id])
            ->execute();
    }
}
