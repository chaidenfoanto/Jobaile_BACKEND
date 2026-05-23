# Jobaile_BACKEND

[![Contributors][contributors-shield]](https://github.com/FranklinJaya2006/Project-RPSLO/graphs/contributors](https://github.com/chaidenfoanto/Jobaile_BACKEND/graphs/contributors)

[contributors-shield]: https://img.shields.io/github/contributors/FranklinJaya2006/Project-RPSLO.svg?style=for-the-badge

[![LinkedIn][linkedin-shield]](https://www.linkedin.com/in/franklin-jaya-6a3697364/)

[linkedin-shield]: https://img.shields.io/badge/LinkedIn-0A66C2?style=for-the-badge&logo=linkedin&logoColor=white



<!-- PROJECT LOGO -->
<br />


<!-- TABLE OF CONTENTS -->
<details>
  <summary>Table of Contents</summary>
  <ol>
    <li>
      <a href="#about-the-project">About The Project</a>
      <ul>
        <li><a href="#built-with">Built With</a></li>
      </ul>
    </li>
    <li>
      <a href="#getting-started">Getting Started</a>
      <ul>
        <li><a href="#prerequisites">Prerequisites</a></li>
        <li><a href="#installation">Installation</a></li>
      </ul>
    </li>
    <li><a href="#usage">Usage</a></li>
    <li><a href="#roadmap">Roadmap</a></li>
    <li><a href="#contributing">Contributing</a></li>
    <li><a href="#license">License</a></li>
    <li><a href="#contact">Contact</a></li>
    <li><a href="#acknowledgments">Acknowledgments</a></li>
  </ol>
</details>



<!-- ABOUT THE PROJECT -->
## About The Project

[![Product Name Screen Shot][product-screenshot]](https://example.com)

There are many great README templates available on GitHub; however, I didn't find one that really suited my needs so I created this enhanced one. I want to create a README template so amazing that it'll be the last one you ever need -- I think this is it.

Here's why:
* Your time should be focused on creating something amazing. A project that solves a problem and helps others
* You shouldn't be doing the same tasks over and over like creating a README from scratch
* You should implement DRY principles to the rest of your life :smile:

Of course, no one template will serve all projects since your needs may be different. So I'll be adding more in the near future. You may also suggest changes by forking this repo and creating a pull request or opening an issue. Thanks to all the people who have contributed to expanding this template!

Use the `BLANK_README.md` to get started.

<p align="right">(<a href="#readme-top">back to top</a>)</p>



### Built With

This section should list any major frameworks/libraries used to bootstrap your project. Leave any add-ons/plugins for the acknowledgements section. Here are a few examples.

* [![YOLO][YOLO.org]][YOLO-url]
* [![Python][Python.org]][Python-url]

<p align="right">(<a href="#readme-top">back to top</a>)</p>

### Project Dependencies

This project uses:

- YOLOv8 for object detection
- OpenCV for camera processing
- RPi.GPIO for Raspberry Pi GPIO control

Example imports used in the project:

```python
import cv2
import RPi.GPIO as GPIO
from ultralytics import YOLO
```

## Getting Started

Follow these steps to set up the YOLOv8 project locally using Python virtual environment (`venv`).

### Prerequisites

Make sure you have installed:

- Python 3.10+
- pip
- Git

Check your installation:

```sh
python --version
pip --version
git --version
```

---

### Installation

1. Clone the repository

```sh
git clone https://github.com/your_username/your_repository.git
```

2. Navigate to the project folder

```sh
cd your_repository
```

3. Create a virtual environment

```sh
python -m venv venv
```

4. Activate the virtual environment

**Windows**
```sh
venv\Scripts\activate
```

**Linux / macOS / Raspberry Pi**
```sh
source venv/bin/activate
```

5. Install required dependencies

```sh
pip install ultralytics opencv-python
```

For Raspberry Pi GPIO support:

```sh
pip install RPi.GPIO
```

6. Download YOLOv8 model weights

Example:

```sh
wget https://github.com/ultralytics/assets/releases/download/v0.0.0/yolov8s.pt
```

Or manually download the model from:

- https://github.com/ultralytics/ultralytics

7. Run the project

```sh
python roda.py
```

---

### Verify YOLOv8 Installation

```sh
yolo version
```

If installed correctly, the YOLOv8 version information will appear.

<p align="right">(<a href="#readme-top">back to top</a>)</p>



<!-- USAGE EXAMPLES -->
## Usage

Use this space to show useful examples of how a project can be used. Additional screenshots, code examples and demos work well in this space. You may also link to more resources.

_For more examples, please refer to the [Documentation](https://example.com)_

<p align="right">(<a href="#readme-top">back to top</a>)</p>

<!-- CONTACT -->
## Contact

Your Name - [@franklinjaya_](https://www.instagram.com/franklinjaya_/) - franklinjaya827@gmail.com

Project Link: [https://github.com/FranklinJaya2006/Project-RPSLO/tree/main](https://github.com/FranklinJaya2006/Project-RPSLO/tree/main)

<p align="right">(<a href="#readme-top">back to top</a>)</p>


<!-- MARKDOWN LINKS & IMAGES -->
<!-- https://www.markdownguide.org/basic-syntax/#reference-style-links -->
[Python.org]: https://img.shields.io/badge/Python-3776AB?style=for-the-badge&logo=python&logoColor=white
[Python-url]: https://www.python.org/
[YOLO.org]: https://img.shields.io/badge/YOLO-Ultralytics-111111?style=for-the-badge
[YOLO-url]: https://ultralytics.com/
