<?php
/**
 * @package midcom_core
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * SQLite cache backend.
 *
 * @package midcom_core
 */
class midcom_core_services_cache_sqlite implements midcom_core_services_cache
{
    private $_db;
    private $_table;
    
    public function __construct()
    {
        $this->_db = new SQLiteDatabase("{$_MIDCOM->configuration->cache_directory}/{$_MIDCOM->configuration->cache_name}.sqlite");
        $this->_table = str_replace(array(
            '.', '-'
        ), '_', $this->_name);
        
        // Check if we have a DB table corresponding to current cache name 
        $result = $this->_db->query("SELECT name FROM sqlite_master WHERE type='table' AND name='{$this->_table}'");
        $tables = $result->fetchAll();
        if (count($tables) == 0)
        {
            /**
             * Creating table for data
             */
            $this->_db->query("CREATE TABLE {$this->_table} (key VARCHAR(255), value TEXT);");
            $this->_db->query("CREATE INDEX {$this->_table}_key ON {$this->_table} (key);");
            
            /**
             * Creating table for tags
             */
            $this->_db->query("CREATE TABLE {$this->_table}_tags (id INTEGER PRIMARY KEY, tag VARCHAR(255));");
            $this->_db->query("CREATE INDEX {$this->_table}_tags ON {$this->_table}_tags (tag);");
            
            /**
             * Creating table for lookup
             */
            $this->_db->query("CREATE TABLE {$this->_table}_key_tags_lookup (tag_id INTEGER, key VARCHAR(255));");
            $this->_db->query("CREATE INDEX {$this->_table}_key_tags_lookup_key ON {$this->_table}_key_tags_lookup (tag_id), {$this->_table}_tags (key);");
        
        }
    }
    
    public function get($key)
    {
        $key = sqlite_escape_string($key);
        $results = $this->_db->query("SELECT value FROM {$this->_table} WHERE key='{$key}'");
        $results = $results->fetchAll();
        if (count($results) == 0)
        {
            return false; // no hit
        }
        
        return $results[0]['value'];
    }
    
    public function get_by_tag($tags)
    {
        $constraint = '';
        if (is_array($tags))
        {
            foreach ($tags as $tag)
            {
                $tag = sqlite_escape_string($tag);
                $constraint .= "{$this->_table}_key_tags.tag='{$tag}' OR ";
            }
            $constraint = substr($constraint, 0, strlen($constraint) - 3);
        }else
        {
            $tags = sqlite_escape_string($tags);
            $constraint = "{$this->_table}_key_tags.tag='{$tag}'";
        }
        // Making a query
        $query = ("SELECT {$this->_table}.key AS key, {$this->_table}.value AS value FROM {$this->_table}
        LEFT JOIN {$this->_table}_key_tags_lookup ON {$this->_table}_key_tags_lookup.key={$this->_table}.key
        LEFT JOIN {$this->_table}_tags ON {$this->_table}_tags.id={$this->_table}_key_tags_lookup.tag_id
        WHERE $constraint
        ");
        
        $results = $this->_db->query($query);
        $results = $results->fetchAll();
        if (count($results) == 0)
        {
            return false; // no hit
        }
        
        return $results;
    }
    
    public function put($key, $data, $timeout = false, $tags = null)
    {
        $key = sqlite_escape_string($key);
        $data = sqlite_escape_string($data);
        $this->_db->query("REPLACE INTO {$this->_table} (key, value) VALUES ('{$key}', '{$data}')");
        if (! is_null($tags))
        {
            if (is_array($tags))
            {
                foreach ($tags as $tag)
                {
                    $tag = sqlite_escape_string($tag);
                    $tag_id = $this->checktag($tag);
                    $this->_db->query("REPLACE INTO {$this->_table}_key_tags_lookup (tag_id, key) VALUES ('{$tag_id}', '{$key}')");
                }
            }
            else 
            {
                $tags = sqlite_escape_string($tags);
                $tag_id = $this->checktag($tags);
                $this->_db->query("REPLACE INTO {$this->_table}_key_tags_lookup (tag_id, key) VALUES ('{$tag_id}', '{$key}')");
            }
        }
    }
    
    public function remove($key)
    {
        $key = sqlite_escape_string($key);
        $this->_db->query("DELETE FROM {$this->_table} WHERE key='{$key}'");    
        $this->_db->query("DELETE FROM {$this->_table}_key_tags_lookup WHERE key='{$key}'");
    }
    
    
    /**
     * Checks if given tag is already created. A key of the tag is returned
     *
     * @param string $tag
     * @return integer tag_id
     */
    private function checktag($tag)
    {
        $results = $this->_db->query("SELECT id FROM {$this->_table}_tags WHERE tag='{$tag}'");
        $results = $results->fetchAll();
        if (count($results) == 0)
        {
            // making new tag and getting it's id
            $this->_db->query("REPLACE INTO {$this->_table}_tags (tag) VALUES ('{$tag}')");
            $result = $this->_db->query("SELECT last_insert_rowid() AS id");
            $results = $results->fetchAll();
        
        }
        return $results[0]['id'];
    }

}
?>