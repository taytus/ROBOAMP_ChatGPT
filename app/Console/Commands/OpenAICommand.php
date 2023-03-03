<?php

namespace App\Console\Commands;

use App\Models\OpenAI;
use Illuminate\Console\Command;
use OpenAI as AI;
use Illuminate\Support\Facades\File;


class OpenAICommand extends Command {
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'ROBOAMP:openai';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Used to test interfacing with OpenAI';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return int
	 */
	//I saved the questions and answers in a database table called open_ai for debugging purposes
	//you don't have to do that, you can save them in a file or whatever you want and remove the database code
	//if you want to save them in a DB, a migration file is included in the package, just run php artisan migrate
	//I've also included code to save the questions and answers in a file, just uncomment the code in the saveReplyToFile() method

	public function handle() {


		$this->info('Starting conversation...');

		$yourApiKey = getenv('OPENAI_API_KEY');
		$client = AI::client($yourApiKey);

		$question = "";
		//exit the conversation by typing quit
		while ($question != 'quit') {
			$question = $this->ask("Ask me something");

			// Save the question in the database/FileSystem
			OpenAI::create(['content' => $question]);
			//$this->saveReplyToFile($question);


			// Get the context for the OpenAI conversation, by default it will load context from the database,
			//but you can also load it from a file, just use this instead: getContextObj(false)

			$obj = $this->getContextObj();
			//use the file instead of the database
			//$obj = $this->getContextObj(false);

			// Send the question to OpenAI and save the response in the database/FileSystem
			$result = $client->chat()->create(['model' => $obj->model,
				'messages' => $obj->messages]);


			$reply = $this->saveReplyToDB($result);
			$this->info($reply);
			//$reply_from_file = $this->saveReplyToFile($result);
			//$this->info($reply_from_file);

		}

		// [OPTIONALLY] - Clear the database/FileSystem when you're done
		OpenAI::truncate();
		//File::delete(base_path('ROBOAMP_openai.txt'));
		$this->info('Exiting...');
	}

	private function getContextObj($useDB = true) {
		$json = '{
            "model": "gpt-3.5-turbo",
            "messages": [
                ' . str_replace("\n", " ", $this->getContext($useDB)) . '
            ]
        }';

		return json_decode($json);
	}

	private function getContext($useDB = true) {
		$messages = '';
		$records = OpenAI::all();
		if ($useDB == false) {
			return $this->getContextFromFile();
		}
		foreach ($records as $index => $record) {
			$content = str_replace('"', '\\"', $record->content);

			// If the index of the current record is odd
			if ($index % 2 == 1) {
				$messages .= '{"role": "assistant", "content": "' . $content . '"},';
			} else {
				// If the index of the current record is even
				$messages .= '{"role": "user", "content": "' . $content . '"},';
			}
		}
		// Remove the trailing comma from the last message
		return rtrim($messages, ',');
	}

	private function getContextFromFile() {
		$filename = base_path('ROBOAMP_openai.txt');
		$messages = "";
		$lines = file($filename, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
		foreach ($lines as $index => $line) {
			$content = trim(str_replace('"', '\\"', $line));
			if ($index % 2 == 0) {
				$messages.= '{"role":"user","content":"' . $content . '"},';

			} else {
				$messages.= '{"role":"assistant","content":"' . $content . '"},';

			}
		}
		return rtrim($messages, ',');

	}


	private function saveReplyToDB($result) {
		$reply = $result['choices'][0]['message']['content'];
		$saveReply = new OpenAI();
		$saveReply->content = ltrim($reply);
		$saveReply->save();

		return $reply;
	}

	private function saveReplyToFile($result) {
		// If $result is a string, convert it to an array with a single element
		if (is_string($result)) {
			$result = array(
				'choices' => array(
					array(
						'message' => array(
							'content' => $result
						)
					)
				)
			);
		}


		$filename = base_path('ROBOAMP_openai.txt');
		$reply = $result['choices'][0]['message']['content'];
		$reply = ltrim($reply);

		// Add a new line after each record
		$reply .= PHP_EOL;

		// Append the record to the file
		file_put_contents($filename, $reply, FILE_APPEND);

		return $reply;
	}


}