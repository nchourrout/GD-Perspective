<?php
/* Copyright (C) 2009-2010 Nicolas Chourrout <nchourrout at gmail dot com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 *
 *
 * Usage : 
 * 	- Demo
 * 		$p = new Perspective();
 * 		$p->demo();
 *   	$p->display();
 *		
 * 	- Rotate test.png 45� around z-axis and display the result
 * 		$p = new Perspective('test.jpg');
 *		$p->rotate(0,0,M_PI/4);
 *		$p->display();
 * 		
 * 	- Rotate test.jpg 45� around z-axis and save it as a png file output.png
 * 		$p = new Perspective('test.jpg');
 * 		$p->rotate(0,0,M_PI/4);
 *		$p->save("output.png");
 *		
 *	- Rotate test.jpg 30� and display it as a gif
 *		$p = new Perspective('test.jpg');
 *		$p->rotate(0,0,M_PI/6);
 *		$p->displayGIF();
 *		
 *	- Create a animated gif of test.png spinning around z-axis
 * 		$p = new Perspective('test.png');
 *		$p->createAnimatedGIF();
 *
*/
include "GIFEncoder.class.php";
/* Todo
* - G�rer transparence avec les fichiers PNGs
* - parler dans l'interface avec des icones repr�sentant roll, pitch and yaw instead of x,y,z
* - Probl�me aux limites : l'algo n'est pas bon
* - Time limit � ne pas exc�der pour la g�n�ration de gifs anim�s
* - la transfo perspective autour de l'axe z ressemble � une transformation affine (les c�t�s oppos�s restent parall�les)
*/

class Perspective{
		//Settings
		private $output_directory = "output_images/";
		private $input_directory = "input_images/";
		
		//Attributes
		private $img;
		private $imgWidth;
		private $imgHeight;
		private $imgName;
		private $ext;

		//Constructor
		function __construct($imgName='demo.png'){
			$this->imgName = $imgName;
			$this->load();
		}
		
		//Public Methods
		
		
		/**
		* Demo Function : displays the image in a 3/4 view
		* @author nchourrout
		* @version 0.1
		*/
		public function demo(){
			$x0 = 0;$y0 = round(($this->imgHeight)/4);
			$x1 = $this->imgWidth/2;$y1 = 0;
			$x2 = $this->imgWidth/2;$y2 = $this->imgHeight;
			$x3 = 0;$y3 = round(3*($this->imgHeight-1)/4);
			
			$this->createPerspective($x0,$y0,$x1,$y1,$x2,$y2,$x3,$y3);
		}
		
		/**
		* Create a perspective view of the original image as if it has been rotated in 3D
		* @author nchourrout
		* @version 0.1
		* @param long $rx Rotation angle around X axis
		* @param long $ry Rotation angle around Y axis
		* @param long $rz Rotation angle around Z axis
		*/
		public function rotate($rx,$ry,$rz){
			$points = $this->getApexes($rx,$ry,$rz);

			//On doit mieux g�rer le fait que l'image r�sultat ne peut pas �tre agrandie sous peine d'avoir des zones blanches manquantes
			$ratio = 2;
			if ($rx!=0 || $ry!=0 || $rz!=0)
				for($i=0;$i<count($points);$i++)
					$points[$i]=array($points[$i][0]/$ratio,$points[$i][1]/$ratio);
					
					
			list($x0,$y0) = $points[1];
			list($x1,$y1) = $points[0];
			list($x2,$y2) = $points[3];
			list($x3,$y3) = $points[2];

			$this->createPerspective($x0,$y0,$x1,$y1,$x2,$y2,$x3,$y3);
		}
		
		/**
		* Create an animated gif of the image rotating around Z axis
		* @author nchourrout
		* @version 0.1
		* @param time_div integer Duration in ms between two frames (default : 50ms)
		*/
		public function createAnimatedGIF($time_div=50){
			$this->ext = "gif";
			for($i=1;$i<6;$i++){
				$angle = 0.1+M_PI/12*$i;
				$this->rotate(0,0,$angle);
				$this->save($i.".gif");
				$frames[] = $this->output_directory.$i.".gif";
				$time[] = $time_div;
			}
			$loops = 0;//infinite
			$gif = new GIFEncoder($frames,$time,$loops,2,0, 0, 0,"url");

			Header ( 'Content-type:image/gif' );
			echo    $gif->GetAnimation ( ); //Modifier cette ligne par quelquechose qui permette juste de stocker l'image dans un fichier
			
			for($i=1;$i<6;$i++)
				@unlink($this->output_directory.$i.".gif");

		}
		
		public function display($outputName=null){
			if($outputName!=null)
				$outputName = $this->output_directory.$outputName;
				
			switch($this->ext){			
				case 'png':
					$this->displayPNG($outputName);
					break;
				case 'gif':
					$this->displayGIF($outputName);
					break;
				case 'jpeg':
				case 'jpg' : 
					$this->displayJPEG($outputName);
					break;
			}			
		}
		
		public function displayJPEG($outputName=null){
			if($outputName==null){
				Header ( 'Content-type:image/jpeg' );
				imagejpeg($this->img);
			}else
				imagejpeg($this->img,$outputName);
		}
		
		public function displayPNG($outputName=null){
			if($outputName==null){
				Header ( 'Content-type:image/png' );
				imagepng($this->img);	
			}else
				imagepng($this->img,$outputName);	
		}
		
		public function displayGIF($outputName=null){
			if($outputName==null){
				Header ( 'Content-type:image/gif' );
				imagegif($this->img);
			}else
				imagegif($this->img,$outputName);
		}
		
		public function save($outputName=null){
			if($outputName==null)
				$outputName = $this->imgName;
			$this->setExt($outputName);
			$this->display($outputName);
		}
		
		public function setInputDirectory($dir){
			$this->input_directory = $dir;
		}
		
		public function setOutputDirectory($dir){
			$this->output_directory = $dir;
		}
		
		//Private Methods
		
		private function load(){
			$imgSize = getimagesize($this->input_directory.$this->imgName);  
			$this->imgWidth = $imgSize[0];
			$this->imgHeight = $imgSize[1]; 
			$this->setExt($this->imgName);
			$path = $this->input_directory.$this->imgName;
			switch($this->ext){			
				case 'png':
					$this->img = imagecreatefrompng($path);
					break;
				case 'gif':
					$this->img = imagecreatefrompng($path);
					break;
				case 'jpeg':
				case 'jpg' : 
					$this->img = imagecreatefromjpeg($path);
					break;
				default : 
					die("Incorrect image file extension");
			}
		}
		
		private function setExt($imgName){
			$this->ext = strtolower(substr(strrchr($imgName,'.'),1));
		}
		
		private function getApexes($rx,$ry,$rz){
			$cx = cos($rx);
			$sx = sin($rx);
			$cy = cos($ry);
			$sy = sin($ry);
			$cz = cos($rz);
			$sz = sin($rz);
		  
			$ex = $this->imgWidth/2;
			$ey = $this->imgHeight/2;
			$ez = max($this->imgHeight,$this->imgWidth)/2;  
		  
			$cam = array($this->imgWidth/2,$this->imgHeight/2,max($this->imgHeight,$this->imgWidth)/2);
			$apexes = array(array(0,$this->imgHeight,0), array($this->imgWidth, $this->imgHeight, 0), array($this->imgWidth, 0, 0), array(0,0,0));
			$points = array();
			
			$i=0;
			foreach($apexes as $pt) {
				$ax = $pt[0];
				$ay = $pt[1];
				$az = $pt[2];
				
				$dx = $cy*($sz*($ax-$cam[1])+$cz*($ax-$cam[0])) - $sy*($az-$cam[2]);
				$dy = $sx*($cy*($az-$cam[2])+$sy*($sz*($ay-$cam[1])+$cz*($ax-$cam[0])))+$cx*($cz*($ay-$cam[1])-$sz*($ax-$cam[0]));
				$dz = $cx*($cy*($az-$cam[2])+$sy*($sz*($ay-$cam[1])+$cz*($ax-$cam[0])))-$sx*($cz*($ay-$cam[1])-$sz*($ax-$cam[0]));
				
				$points[$i] = array(round(($dx-$ex)/($ez/$dz)),round(($dy-$ey)/($ez/$dz)));
				$i++;
			}
			return $points;
		}
		
		private function createPerspective($x0,$y0,$x1,$y1,$x2,$y2,$x3,$y3){
			$SX = max($x0,$x1,$x2,$x3);
			$SY = max($y0,$y1,$y2,$y3);

			$newImage = imagecreatetruecolor($SX, $SY);
			$bg_color=ImageColorAllocateAlpha($newImage,255,255,255,0); 
			imagefill($newImage, 0, 0, $bg_color);
			for ($y = 0; $y < $this->imgHeight; $y++) {
				for ($x = 0; $x < $this->imgWidth; $x++) {
					list($dst_x,$dst_y) = $this->corPix($x0,$y0,$x1,$y1,$x2,$y2,$x3,$y3,$x,$y,$this->imgWidth,$this->imgHeight);
					imagecopy($newImage,$this->img,$dst_x,$dst_y,$x,$y,1,1);
				}
			}
			$this->img = $newImage;
		}
		
		private function corPix($x0,$y0,$x1,$y1,$x2,$y2,$x3,$y3,$x,$y,$SX,$SY) {
			return $this->intersectLines(
				(($SY-$y)*$x0 + ($y)*$x3)/$SY, (($SY-$y)*$y0 + $y*$y3)/$SY,
				(($SY-$y)*$x1 + ($y)*$x2)/$SY, (($SY-$y)*$y1 + $y*$y2)/$SY,
				(($SX-$x)*$x0 + ($x)*$x1)/$SX, (($SX-$x)*$y0 + $x*$y1)/$SX,
				(($SX-$x)*$x3 + ($x)*$x2)/$SX, (($SX-$x)*$y3 + $x*$y2)/$SX);
		}

		private function det($a,$b,$c,$d) {
			return $a*$d-$b*$c;
		}

		private function intersectLines($x1,$y1,$x2,$y2,$x3,$y3,$x4,$y4) {
			$d = $this->det($x1-$x2,$y1-$y2,$x3-$x4,$y3-$y4);
  
			if ($d==0) $d = 1;
  
			$px = $this->det($this->det($x1,$y1,$x2,$y2),$x1-$x2,$this->det($x3,$y3,$x4,$y4),$x3-$x4)/$d;
			$py = $this->det($this->det($x1,$y1,$x2,$y2),$y1-$y2,$this->det($x3,$y3,$x4,$y4),$y3-$y4)/$d;
			return array($px,$py);
		}
		
}
?>
