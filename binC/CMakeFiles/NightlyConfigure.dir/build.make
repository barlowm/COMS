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

# Utility rule file for NightlyConfigure.

# Include the progress variables for this target.
!include CMakeFiles\NightlyConfigure.dir\progress.make

CMakeFiles\NightlyConfigure:
	"C:\CMake 2.8\bin\ctest.exe" -D NightlyConfigure

NightlyConfigure: CMakeFiles\NightlyConfigure
NightlyConfigure: CMakeFiles\NightlyConfigure.dir\build.make
.PHONY : NightlyConfigure

# Rule to build all files generated by this target.
CMakeFiles\NightlyConfigure.dir\build: NightlyConfigure
.PHONY : CMakeFiles\NightlyConfigure.dir\build

CMakeFiles\NightlyConfigure.dir\clean:
	$(CMAKE_COMMAND) -P CMakeFiles\NightlyConfigure.dir\cmake_clean.cmake
.PHONY : CMakeFiles\NightlyConfigure.dir\clean

CMakeFiles\NightlyConfigure.dir\depend:
	$(CMAKE_COMMAND) -E cmake_depends "Borland Makefiles" C:\projects\Web-Automation-Testing-Framework C:\projects\Web-Automation-Testing-Framework C:\projects\Web-Automation-Testing-Framework\binC C:\projects\Web-Automation-Testing-Framework\binC C:\projects\Web-Automation-Testing-Framework\binC\CMakeFiles\NightlyConfigure.dir\DependInfo.cmake --color=$(COLOR)
.PHONY : CMakeFiles\NightlyConfigure.dir\depend
