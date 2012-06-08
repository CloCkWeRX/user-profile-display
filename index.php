<?php
/**
 * A model describing a person
 */
class Person implements JsonSerializable {
    protected $name;
    protected $age;
    protected $description;
    protected $img;

    public function __construct($name, $age, $description, $img) {
        $this->name = (string)$name;
        $this->age = (int)$age;
        $this->description = (string)$description;
        $this->img = (string)$img;
    }

    public function getName() {
        return $this->name;
    }

    public function getAge() {
        return $this->age;
    }

    public function getDescription() {
        return $this->description;
    }

    public function getPicture() {
        return $this->img;
    }

	/**
	 * Shiny new PHP 5.4+ method of doing this.
	 */
    public function jsonSerialize() {
        return array(
			"name" => $this->getName(),
			"picture" => $this->getPicture(),
			"description" => $this->getDescription(),
			"age" => $this->getAge(),
		);
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
  <!-- TODO: Should be more polite and add as a git submodule -->
  <script type="text/javascript" src="https://raw.github.com/jquery/jquery-tmpl/master/jquery.tmpl.js"></script>
  <script type="text/javascript">
	/** @todo Go grab some other existing control */
	var paginator = {
		pos: 0,
	    increment: 2,
		next: function() {
			paginator.pos += paginator.increment;

			paginator.load();
		},
		prev: function() {
			if (paginator.pos - paginator.increment > 0)
			{
				paginator.pos -= paginator.increment;
				paginator.load();
			}
		},
	    load: function () {
			$.ajax({
					url: '?action=display&start=' + paginator.pos,
					dataType: 'json',
					success: function( response ) {
						$("#results *").remove();
						$( '#profileTemplate' ).tmpl(response).appendTo("#results");
					}
				});
		}
	}
  </script>

  <style type="text/css">
	body {
		background-color: rgb(240, 240, 240);
		margin: auto;
		font-family: Arial;
		font-size: 10pt;
		width: 400px;
    }

	h1, h2 {
		font-family: Verdana;
	}

	img.picture {
		width: 100px;
		height: 80px;
		border: 1px solid black;
    }

	.profile {
		float: left;
		border: 1px solid rgb(230, 230, 230);
		padding: 0.5em;
		margin: 0.1em;
		max-width: 180px;
		height: 200px;
	}


	#results {
		clear: both;
	}
  </style>
 </head>

 <body>
  <h1>Our team</h1>
  <p>The Channel 4 news team is a go-get-'em, action packed collection of adrenaline fueled <s>paper pushers who find their content on youtube</s> journalists. Meet the dynamic duos below!</p>
  <div id="results"></div>
       <script type="text/template" id="profileTemplate">
		 <div class="profile">
		    <h2 class="name">${name}, ${age}</h2>
			<img src="${picture}" class="picture" alt="{$name}" />
			<p class="description">${description}</p>
		</div>
     </script>
   <p><a href="#results" onclick="paginator.prev(); return false;">Prev</a> <a href="#results" onclick="paginator.next(); return false;">Next</a></p>

   <script type="text/javascript">
   paginator.load();
   </script>

 </body>
</html>
