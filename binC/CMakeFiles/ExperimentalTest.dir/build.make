# CMAKE generated file: DO NOT EDIT!
# Generated by "Borland Makefiles" Generator, CMake Version 2.8

#=============================================================================
# Special targets provided by cmake.

# Disable implicit rules so canonical targets will work.
.SUFFIXES:

.SUFFIXES: .hpux_make_needs_suffix_list

# Suppress display of executed commands.
$(VERBOSE).SILENT:

# A target that is always out of date.
cmake_force: NUL
.PHONY : cmake_force

#=============================================================================
# Set environment variables for the build.

!IF "$(OS)" == "Windows_NT"
NULL=
!ELSE
NULL=nul
!ENDIF
SHELL = cmd.exe

# The CMake executable.
CMAKE_COMMAND = "C:\CMake 2.8\bin\cmake.exe"

# The command to remove a file.
RM = "C:\CMake 2.8\bin\cmake.exe" -E remove -f

# Escaping for special characters.
EQUALS = =

# The program to use to edit the cache.
CMAKE_EDIT_COMMAND = "C:\CMake 2.8\bin\cmake-gui.exe"

# The top-level source directory on which CMake was run.
CMAKE_SOURCE_DIR = C:\projects\Web-Automation-Testing-Framework

# The top-level build directory on which CMake was run.
CMAKE_BINARY_DIR = C:\projects\Web-Automation-Testing-Framework\binC

# Utility rule file for ExperimentalTest.

# Include the progress variables for this target.
!include CMakeFiles\ExperimentalTest.dir\progress.make

CMakeFiles\ExperimentalTest:
	"C:\CMake 2.8\bin\ctest.exe" -D ExperimentalTest

ExperimentalTest: CMakeFiles\ExperimentalTest
ExperimentalTest: CMakeFiles\ExperimentalTest.dir\build.make
.PHONY : ExperimentalTest

# Rule to build all files generated by this target.
CMakeFiles\ExperimentalTest.dir\build: ExperimentalTest
.PHONY : CMakeFiles\ExperimentalTest.dir\build

CMakeFiles\ExperimentalTest.dir\clean:
	$(CMAKE_COMMAND) -P CMakeFiles\ExperimentalTest.dir\cmake_clean.cmake
.PHONY : CMakeFiles\ExperimentalTest.dir\clean

CMakeFiles\ExperimentalTest.dir\depend:
	$(CMAKE_COMMAND) -E cmake_depends "Borland Makefiles" C:\projects\Web-Automation-Testing-Framework C:\projects\Web-Automation-Testing-Framework C:\projects\Web-Automation-Testing-Framework\binC C:\projects\Web-Automation-Testing-Framework\binC C:\projects\Web-Automation-Testing-Framework\binC\CMakeFiles\ExperimentalTest.dir\DependInfo.cmake --color=$(COLOR)
.PHONY : CMakeFiles\ExperimentalTest.dir\depend
