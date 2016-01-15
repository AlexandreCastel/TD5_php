<?php

class Model
{
    protected $pdo;

    public function __construct(array $config)
    {
        try {
            if ($config['engine'] == 'mysql') {
                $this->pdo = new \PDO(
                    'mysql:dbname='.$config['database'].';host='.$config['host'],
                    $config['user'],
                    $config['password']
                );
                $this->pdo->exec('SET CHARSET UTF8');
            } else {
                $this->pdo = new \PDO(
                    'sqlite:'.$config['file']
                );
            }
        } catch (\PDOException $error) {
            throw new ModelException('Unable to connect to database');
        }
    }

    /**
     * Tries to execute a statement, throw an explicit exception on failure
     */
    protected function execute(\PDOStatement $query, array $variables = array())
    {
        if (!$query->execute($variables)) {
            $errors = $query->errorInfo();
            throw new ModelException($errors[2]);
        }

        return $query;
    }

    /**
     * Inserting a book in the database
     */
    public function insertBook($title, $author, $synopsis, $image, $copies)
    {
        $query = $this->pdo->prepare('INSERT INTO livres (titre, auteur, synopsis, image)
            VALUES (?, ?, ?, ?)');
        $this->execute($query, array($title, $author, $synopsis, $image));

        // TODO: Créer $copies exemplaires
        $id_book = $this->pdo->lastInsertId();
        for($i = 0; $i < $copies; $i++){
            $query = $this->pdo->prepare('INSERT INTO exemplaires (book_id) VALUES (?)');
            $this->execute($query, array($id_book));
        }
        // fin TODO
    }

    /**
     * Getting all the books
     */
    public function getBooks()
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres');

        $this->execute($query);

        return $query->fetchAll();
    }

    /**
    *   Getting book
    */
    public function getBook($id)
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres WHERE id = ?');
        $query->execute(array($id));
        return $query->fetch();
    }

    /**
    *   Getting last books
    */
    public function getLastBooks(){
        $query = $this->pdo->prepare('SELECT livres.* FROM livres ORDER BY id DESC LIMIT 0, 6');
        $this->execute($query);
        return $query->fetchAll();
    }

    /**
    *   Borrow in database
    */
    public function insertBorrow($id_exemplaire, $personne, $dateFin)
    {
        $dateDebut = date("d/m/Y");
        $query = $this->pdo->prepare('INSERT INTO emprunts (personne, exemplaire, debut, fin)
            VALUES (?, ?, ?, ?)');
        $this->execute($query, array($personne, $id_exemplaire, $dateDebut, $dateFin));
    }
    public function getExemplaires($id)
    {
        $query = $this->pdo->prepare('SELECT exemplaires.* FROM exemplaires WHERE book_id = ?');
        $query->execute(array($id));
        return $query->fetchAll();
    }
    public function getExemplaire($id_book, $id_exemplaire)
    {
        $query = $this->pdo->prepare('SELECT exemplaires.* FROM exemplaires WHERE book_id = ? AND id = ?');
        $query->execute(array($id_book, $id_exemplaire));
        return $query->fetch();
    }
    public function getBorrow(){
        $query = $this->pdo->prepare('SELECT emprunts.* FROM emprunts');
        $this->execute($query);
        $data = $query->fetchAll();
        foreach($data as $key => $attribut){
            if ($attribut = 'id'){
                $TableauxFinal[$key][$attribut] = $data[$key][$attribut];
            }
            if ($attribut = 'personne'){
                $TableauxFinal[$key][$attribut] = $data[$key][$attribut];
            }
            if ($attribut = 'exemplaire'){
                $TableauxFinal[$key][$attribut] = $data[$key][$attribut];
            }
            if ($attribut = 'fin'){
                $dateFin = substr($data[$key][$attribut], 0, 10);
                $tampon = explode('-', $dateFin);
                $dateFinTimeFr = $tampon[2].'/'.$tampon[1].'/'.$tampon[0];
                $TableauxFinal[$key][$attribut] = $dateFinTimeFr;
            }
            if ($attribut = 'fini'){
                $TableauxFinal[$key][$attribut] = $data[$key][$attribut];
            }
        }
        return $TableauxFinal;
    }
    public function getBorrowFromExemplaireNotAvailable($id_exemplaire){
        $query = $this->pdo->prepare('SELECT emprunts.* FROM emprunts WHERE exemplaire = ? AND (emprunts.fin > NOW() OR emprunts.fini = 1)');
        $query->execute(array($id_exemplaire));
        return $query->fetch();
    }
}
