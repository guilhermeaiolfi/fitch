-- phpMyAdmin SQL Dump
-- version 4.1.12
-- http://www.phpmyadmin.net
--
-- Host: 127.0.0.1
-- Generation Time: 19-Maio-2016 às 21:53
-- Versão do servidor: 5.6.16
-- PHP Version: 5.5.11

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;

--
-- Database: `htsql`
--

-- --------------------------------------------------------

--
-- Estrutura da tabela `courses`
--

CREATE TABLE IF NOT EXISTS `courses` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=2 ;

--
-- Extraindo dados da tabela `courses`
--

INSERT INTO `courses` (`id`, `name`) VALUES
(1, 'Computer Science');

-- --------------------------------------------------------

--
-- Estrutura da tabela `departments`
--

CREATE TABLE IF NOT EXISTS `departments` (
  `id` int(10) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Extraindo dados da tabela `departments`
--

INSERT INTO `departments` (`id`, `name`) VALUES
(1, 'Department #1'),
(2, 'Department #2');

-- --------------------------------------------------------

--
-- Estrutura da tabela `department_course`
--

CREATE TABLE IF NOT EXISTS `department_course` (
  `department_id` int(10) NOT NULL,
  `course_id` int(10) NOT NULL,
  PRIMARY KEY (`department_id`,`course_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `department_course`
--

INSERT INTO `department_course` (`department_id`, `course_id`) VALUES
(1, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `programs`
--

CREATE TABLE IF NOT EXISTS `programs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `school_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Extraindo dados da tabela `programs`
--

INSERT INTO `programs` (`id`, `name`, `school_id`) VALUES
(1, 'Program #1', 1),
(2, 'Program #2', 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `schools`
--

CREATE TABLE IF NOT EXISTS `schools` (
  `id` int(10) NOT NULL,
  `name` varchar(255) NOT NULL,
  `director_id` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `schools`
--

INSERT INTO `schools` (`id`, `name`, `director_id`) VALUES
(1, 'School #1', 1),
(2, 'School #2', 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `school_department`
--

CREATE TABLE IF NOT EXISTS `school_department` (
  `department_id` int(10) NOT NULL,
  `school_id` int(10) NOT NULL,
  PRIMARY KEY (`department_id`,`school_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `school_department`
--

INSERT INTO `school_department` (`department_id`, `school_id`) VALUES
(1, 1),
(1, 2),
(2, 1);

-- --------------------------------------------------------

--
-- Estrutura da tabela `school_program`
--

CREATE TABLE IF NOT EXISTS `school_program` (
  `school_id` int(11) NOT NULL,
  `program_id` int(11) NOT NULL,
  PRIMARY KEY (`school_id`,`program_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

--
-- Extraindo dados da tabela `school_program`
--

INSERT INTO `school_program` (`school_id`, `program_id`) VALUES
(1, 1),
(2, 2);

-- --------------------------------------------------------

--
-- Estrutura da tabela `users`
--

CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=3 ;

--
-- Extraindo dados da tabela `users`
--

INSERT INTO `users` (`id`, `name`) VALUES
(1, 'Guilherme'),
(2, 'Godofredo');

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
