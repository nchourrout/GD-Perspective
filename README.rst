================
 GD-Perspective
================

GD-Perspective allows you to apply a 3D perspective effect to 2D images
The perspective can be applied by specifying the new coordinates of the image angles
or by specifying rotations around the x, y and z axis

Usage
=====

Demo::

  $p = new Perspective();
  $p->demo();
  $p->display();

Rotate test.png 45 degrees around z-axis and display the result::

  $p = new Perspective('test.jpg');
  $p->rotate(0,0,M_PI/4);
  $p->display();

Rotate test.jpg 45 degrees around z-axis and save it as a png file output.png::

  $p = new Perspective('test.jpg');
  $p->rotate(0,0,M_PI/4);
  $p->save("output.png");

Rotate test.jpg 30? and display it as a gif::

  $p = new Perspective('test.jpg');
  $p->rotate(0,0,M_PI/6);
  $p->displayGIF();

Create a animated gif of test.png spinning around z-axis::

  $p = new Perspective('test.png');
  $p->createAnimatedGIF(); 

License
=======

This software is distributed under the GPL_ license.

.. _GPL: http://www.gnu.org/licenses/gpl.html

Author
======

Nicolas Chourrout : `website`_

.. _website: http://chourrout.com


More information
================

* the source code is `hosted in GitHub`_

.. _hosted in GitHub: http://github.com/nchourrout/GD-Perspective

