<?php
declare (strict_types=1);


namespace SilangPHP\Migrate\Classes;

use SilangPHP\Migrate\Helper\TemplateHelper;

/**
 *
 * FILE_NAME: CommandLineWriter.php
 * User: OneXian
 * Date: 2021.11.09
 */
class CommandLineWriter
{
    /**
     * A single, static instance of this class.  Singleton pattern.
     *
     * @var CommandLineWriter
     */
    static private $instance;

    /**
     * A collection of text items to write to the terminal.
     *
     * @var array
     */
    private $text;

    /**
     * The maximum width to wrap the text displayed.
     *
     * @var int
     */
    public $maxWidth;

    /**
     * Object constructor.
     *
     * @return CommandLineWriter
     */
    private function __construct()
    {
        $this->text = array();
        $this->maxWidth = 80;
    }

    /**
     * Returns a single static instance of this object.
     *
     * @return CommandLineWriter
     */
    static public function getInstance()
    {
        if (self::$instance == null)
        {
            self::$instance = new CommandLineWriter();
        }
        return self::$instance;
    }

    /**
     * Adds text to the collection to be displayed to the terminal.
     *
     * @param string $text   the text to add to the collection
     * @param int    $indent the number of spaces to indent each line of this text
     *
     * @return void
     */
    public function addText($text, $indent = 0)
    {
        $obj = (object) array();
        $obj->text = $text;
        $obj->indent = $indent;
        $this->text[] = $obj;
        return;
    }

    /**
     * Adds the header to the $text property.
     *
     * @return void
     */
    private function addHeader()
    {
        $blank = (object) array();
        $blank->text = ' ';
        $blank->indent = 0;
        array_unshift($this->text, $blank);

        $max_line_len = $this->maxWidth - 12;
        $bar = '';
        for ($i = 0; $i < $max_line_len; $i++)
        {
            $bar .= "*";
        }
        $bar .= ' v' . M_VERSION . ' ***';
        $bar_obj = (object) array();
        $bar_obj->text = $bar;
        $bar_obj->indent = 0;
        array_unshift($this->text, $bar_obj);

        $lines = TemplateHelper::getTemplateAsArrayOfLines('header.txt');

        $start = count($lines) - 1;
        for ($i = $start; $i >=0; $i--)
        {
            $line = $lines[$i];
            $a = $this->maxWidth - strlen($line);
            $indent = floor($a / 2);
            $txt = (object) array();
            $txt->text = $line;
            $txt->indent = 0;
            array_unshift($this->text, $txt);
        }
        return;
    }

    /**
     * Adds the footer to the $text property.
     *
     * @return void
     */
    private function addFooter()
    {
        $this->addText(' ');
        $max_line_len = $this->maxWidth - 1;
        $bar = '';
        for ($i = 0; $i < $max_line_len; $i++)
        {
            $bar .= "*";
        }
        $bar_obj = (object) array();
        $bar_obj->text = $bar;
        $bar_obj->indent = 0;
        array_push($this->text, $bar_obj);
        return;
    }

    /**
     * Writes the header, text, and footer.
     *
     * @return void
     */
    public function write()
    {
        $this->writeHeader();
        $this->writeText();
        $this->writeFooter();
        return;
    }

    /**
     * Generates and echos the text to the terminal.
     *
     * @return string
     */
    private function writeText()
    {
        $body = '';
        $all_lines = array();
        $max_line_len = $this->maxWidth;
        foreach ($this->text as $obj)
        {
            $wrap_point = $max_line_len - $obj->indent - 1;
            $indent = '';
            for ($i = 0; $i < $obj->indent; $i++)
            {
                $indent .= " ";
            }
            $lines_str = wordwrap($obj->text, $wrap_point, "---");
            $lines = explode("---", $lines_str);
            foreach ($lines as $line)
            {
                $all_lines[] = $indent . $line;
            }
        }
        foreach ($all_lines as $line)
        {
            $body .= $line;
            for ($i = 0; $i < $max_line_len - strlen($line); $i++)
            {
                $body .= " ";
            }
            $body .= "\n";
        }
        echo $body;
        return;
    }

    /**
     * Writes a single line to the console.
     *
     * @return void
     */
    public function writeLine($txt, $ind = 0)
    {
        $obj = (Object) array();
        $obj->text = $txt;
        $obj->indent = $ind;
        $max_line_len = $this->maxWidth;
        $wrap_point = $max_line_len - $obj->indent - 1;
        $indent = '';
        $all_lines = array();
        for ($i = 0; $i < $obj->indent; $i++)
        {
            $indent .= " ";
        }
        $lines_str = wordwrap($obj->text, $wrap_point, "---");
        $lines = explode("---", $lines_str);
        $body = '';
        foreach ($lines as $line)
        {
            $all_lines[] = $indent . $line;
        }
        foreach ($all_lines as $line)
        {
            $body .= $line;
            for ($i = 0; $i < $max_line_len - strlen($line); $i++)
            {
                $body .= " ";
            }
            $body .= "\n";
        }
        echo $body;
        return;
    }

    /**
     * Writes the header to the console.
     *
     *
     * @return void
     */
    public function writeHeader()
    {
        $text = $this->text;
        $this->text = array();
        $this->addHeader();
        $this->writeText();
        $this->text = $text;
        return;
    }

    /**
     * Writes the footer to the console.
     *
     * @return void
     */
    public function writeFooter()
    {
        $text = $this->text;
        $this->text = array();
        $this->addFooter();
        $this->writeText();
        $this->text = $text;
    }
}