<?php
/**
 * Plugin Numbered Headings: Plugin to add numbered headings to DokuWiki-Syntax
 *
 * Usage:   ====== - Heading Level 1======
 *          ===== - Heading Level 2 =====
 *          ===== - Heading Level 2 =====
 *                   ...
 *
 * =>       1 Heading Level 1
 *              1.1 Heading Level 2
 *              1.2 Heading Level 2
 *          ...
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Lars J. Metz <dokuwiki@meistermetz.de>
 */

if(!defined('DOKU_INC')) define('DOKU_INC',realpath(dirname(__FILE__).'/../../').'/');
if(!defined('DOKU_PLUGIN')) define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');
require_once(DOKU_PLUGIN.'syntax.php');

class syntax_plugin_numberedheadings extends DokuWiki_Syntax_Plugin {

    // is now set in configuration manager
    var $startlevel = 0; // level to start with numbered headings (default = 2)
    var $tailingdot = 0; // show a tailing dot after numbers (default = 0)

    var $levels = array( '======'=>1,
                         '====='=>2,
                         '===='=>3,
                         '==='=>4,
                         '=='=>5);

    var $headingCount =
                 array(  1=>0,
                         2=>0,
                         3=>0,
                         4=>0,
                         5=>0);

    function syntax_plugin_numberedheadings() {
        $this->startlevel = $this->getConf('startlevel');
        $this->tailingdot = $this->getConf('tailingdot');
    }

    function getInfo(){
        return array( 'author' => 'Lars J. Metz',
                      'email'  => 'dokuwiki@meistermetz.de',
                      'date'   => '2010-05-12',
                      'name'   => 'Plugin: Numbered Headings',
                      'desc'   => 'Adds numbered headings to DokuWiki',
                      'url'    => 'http://www.dokuwiki.org/plugin:NumberedHeadings');
    }

    function getType(){
        return 'substition';
    }

    function connectTo($mode) {
        $this->Lexer->addSpecialPattern( '{{header>[1-5]}}',
                                         $mode,
                                         'plugin_numberedheadings');
        // added new parameter (matches the parameter name for better recognition)
        $this->Lexer->addSpecialPattern( '{{startlevel>[1-5]}}',
                                         $mode,
                                         'plugin_numberedheadings');
        $this->Lexer->addSpecialPattern( '^[ \t]*={2,6}\s?\-[^\n]+={2,6}[ \t]*(?=\n)',
                                         $mode,
                                         'plugin_numberedheadings');
    }

    function getSort() {
        return 45;
    }

    function handle($match, $state, $pos, &$handler){

        // obtain the startlevel from the page if defined
        if (preg_match('/{{[a-z]{6,10}>([1-5]+)}}/', $match, $startlevel)) {
            $this->startlevel = $startlevel[1];
            return true;
        }

        // define the level of the heading
        preg_match('/(={2,})/', $match, $heading);
        $level = $this->levels[$heading[1]];

        // obtain the startnumber if defined
        if (preg_match('/#([0-9]+)\s/', $match, $startnumber) && ($startnumber[1]) > 0) {
            $this->headingCount[$level] = $startnumber[1];

            //delete the startnumber-setting markup from string
            $match = preg_replace('/#[0-9]+\s/', ' ', $match);

        } else {

            // increment the number of the heading
            $this->headingCount[$level]++;
        }

        // build the actual number
        $headingNumber = '';
        for ($i=$this->startlevel;$i<=5;$i++) {

            // reset the number of the subheadings
            if ($i>$level) {
                $this->headingCount[$i] = 0;
            }

            // build the number of the heading
            $headingNumber .= ($this->headingCount[$i]!=0) ? $this->headingCount[$i].'.' : '';
        }

        // delete the tailing dot if wished (default)
        $headingNumber = ($this->tailingdot) ? $headingNumber : substr($headingNumber,0,-1);

        // insert the number...
        $match = preg_replace('/(={2,}\s?)\-/', '${1}'.$headingNumber, $match);

        // ... and return to original behavior
        $handler->header($match, $state, $pos);

        return true;
    }

    function render($format, &$renderer, $data) {
        //do nothing (already done by original render-method)
    }
}
//Setup VIM: ex: et ts=4 enc=utf-8 :