<?php
/**
 * A model describing a person
 */
class Person {
    protected $name;
    protected $age;
    protected $description;
    protected $img;

    public function __construct($name, $age, $description, $img) {
        $this->name = (string)$name;
        $this->age = (int)$age;
        $this->description = (string)$description;
        $this->description = (string)$description;
    }

    public function getName() {
        return $this->name;
    }

    public function getAge() {
        return $this->age;
    }

    public function getDescription() {
        return $this->name;
    }

    public function getPicture() {
        return $this->img;
    }
}

/**
 * A factory style class to locate a person from a given resource
 */
class PersonLocator {

    public function __construct(DB $db) {
        $this->db = $db;
    }

    /**
     * List a number of people from the data store
     */
    public function display($start = 0, $max = 2) {
        $people = array();

        /** @todo If we had a real DB layer, we'd avoid sql injection better */
        $results = $this->db->query(
            "SELECT name, age, description, img FROM person LIMIT " . (int)$max . " OFFSET " . (int)$start
        );


        foreach ($results as $data) {
            $people[] = $this->build($data);
        }

        return $people;
    }

    /**
     * Take a structured hash and map it to a Person model.
     *
     * IE: From a DB result.
     */
    public function build($data) {
        return new Person(
            $data['name'], $data['age'],
            $data['description'], $data['img']
        );
    }
}

/**
 * Mock DB layer
 */
class DB {

    public function query($sql) {
        $start = "SELECT name, age, description, img FROM person ";
        
        switch (str_replace($start, "", $sql)) {
            default:
            case " LIMIT 2 OFFSET 0":
                return array(
                    array(
                        "name" => "Dave",
                        "age" => "67",
                        "description" => "Dave enjoys long sandy walks on the beach.",
                        "img" => 'http://upload.wikimedia.org/wikipedia/commons/8/80/Knut_IMG_8095.jpg'
                    ),
                    array(
                        "name" => "Bob",
                        "age" => "55",
                        "description" => "Bob is Dave's life long nemisis.",
                        "img" => "http://4.bp.blogspot.com/-QLhr8jQKnUk/TcsGPThR30I/AAAAAAAABsU/GaQiKHQlvFA/s1600/bob-marley-happy.jpg"
                    ),
                );
            break;
            case " LIMIT 2 OFFSET 2":
                return array(
                    array(
                        "name" => "Martin",
                        "age" => "21",
                        "description" => "Martin plots constantly to own a private island. He is often quoted as refusing to allow Dave to walk upon it, should he ever obtain the funds.",
                        "img" => "http://www.fluidosol.se/MartinJuly08-small.jpg"
                    ),
                    array(
                        "name" => "Sally",
                        "age" => "33",
                        "description" => "Sally is the cat herder of the team, often acting as an arbitrator between heated infighting",
                        "img" => "http://www.nndb.com/people/607/000023538/struthers1-sized.jpg"
                    ),
                );
        }
    }

}


// And here's the backend, for handling json. Would be in it's own controller/action normally, but we can leave it here momentarily.
if (isset($_GET['action']) && $_GET['action'] == 'display') {
    $locator = new PersonLocator(new DB());


    $start = 0;
    if (isset($_GET['start']) && $_GET['start'] >= 0) {
        $start = $_GET['start'];
    }

    header('content-type: application/json');
    print json_encode($locator->display($start, 2));
    die();
}

// And here's the view bits.
?>
<!DOCTYPE html>
<html>
 <head>
  <title>Our team</title>
  <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>

  <script type="text/javascript">
	/** @todo Go grab some other existing control */
	var paginator = {
		pos: 0,
		next: function() {
			this.pos++;
		},
		prev: function() {
			if (this.pos > 0)
			{
				this.pos--;
			}
		},
	    load: function () {
			$.ajax({
					url: '?action=display&start=' + this.pos,
					dataType: 'json',
					success: function( response ) {
						$( '#profileTemplate' ).render( response ).appendTo( "#results" );
					}
				});
		}
	}
  </script>

  <style type="text/css">
	body {
		background-color: rgb(240, 240, 240);
		margin: 2em;
    }

	img.picture {
		max-width: 100px;
		max-height: 150px;
    }
  </style>
 </head>

 <body>
  <h1>Our team</h1>

  <div id="results">
     <script type="text/template" id="profileTemplate">
        <h2 class="name">${name}, ${age}</h2>
        <img src="" class="picture" alt="{$name"}/>
        <p class="description">${description}</p>
     </script>
  </div>
  
 </body>
</html>
