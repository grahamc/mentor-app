<?php
/**
 * @author Stevan Goode <stevan@stevangoode.com>
 * @licence http://opensource.org/licences/MIT MIT
 * @package MentorApp
 */

namespace MentorApp;

/**
 * Class to interface with the data store and perform necessary actions with Tag objects
 */
class TagService
{
    /**
     * @var \PDO $db PDO instance of the data store connection
     */
    protected $db;

    /**
     * @var string $tag_table The name of the table for tags
     */
    protected $tag_table = 'tag';

    /**
     * The standard constructor
     *
     * @param \PDO $db The data store connection
     */
    public function __construct(\PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Fetches a tag from the data store
     *
     * @param Tag $tag The tag to fill
     * @return Tag The filled tag
     * @throws \InvalidArgumentException
     */
    public function retrieve (Tag $tag)
    {
        if (empty($tag->name)) {
            throw new \InvalidArgumentException('Supplied tag does not have a name set');
        }
        $query = 'select * from ' . $this->tag_table . ' where name = :name';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':name' => $tag->name]);
        if (!$stmt->rowCount()) {
            return $tag;
        }
        $fields = $stmt->fetch(\PDO::FETCH_ASSOC);

        $tag->added = new \DateTime($fields['added']);
        $tag->authorized = ($fields['authorized'] == 1);

        return $tag;
    }

    /**
     * Searches for a tag based on a partial textual match
     *
     * @param string $term The term to search for
     * @return array The matching tags
     * @throws \InvalidArgumentException
     */
    public function searchByTerm ($term)
    {
        if (empty($term)) {
            throw new \InvalidArgumentException('No search term supplied');
        }
        $query = 'select * from ' . $this->tag_table . ' where tag like "%:term%"';
        $stmt = $this->db->prepare($query);
        $stmt->execute([':term' => $term]);
        if (!$stmt->rowCount()) {
            return [];
        }
        $return = [];

        while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
            $tag = new Tag();
            $tag->name       = $row['name'];
            $tag->added      = new \DateTime($row['added']);
            $tag->authorized = ($row['authorized'] == 1);

            $return[] = $tag;
        }
        return $return;
    }

    /**
     * Saves a tag to the database
     *
     * @param Tag $tag The tag to save
     * @return TagService
     * @throws \InvalidArgumentException
     */
    public function save (Tag $tag)
    {
        if (empty($tag->name)) {
            throw new \InvalidArgumentException('Tag is missing a name');
        }
        $name = $tag->name;
        $authorized = $tag->authorized?1:0;
        $added = $tag->added->format('Y-m-d H:i:s');

        $query = 'insert into ' . $this->tag_table .' (
            name,
            authorized,
            added
        ) values (
            :name,
            :authorized,
            :added
        ) on duplicate key update
            authorized = :authorized
        ';

        $stmt = $this->db->prepare($query);
        $stmt->execute([':name' => $name, ':authorized' => $authorized, ':added' => $added]);

        return $this;
    }
}
