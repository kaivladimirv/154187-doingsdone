CREATE TABLE users (
  code INTEGER AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL,
  email VARCHAR(100) NOT NULL,
  password VARCHAR(50) NOT NULL,
  contacts TEXT,
  date_registration TIMESTAMP NOT NULL,

  UNIQUE KEY (email)
);

CREATE TABLE projects (
  code INTEGER AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL
);

CREATE TABLE tasks (
  code INTEGER AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(255) NOT NULL,
  project_code INTEGER NOT NULL,
  creator_code INTEGER NOT NULL,
  date_creation TIMESTAMP NOT NULL,
  date_deadline DATE,
  path_to_file VARCHAR(255),
  is_done BIT NOT NULL DEFAULT 0,

  FOREIGN KEY (project_code) REFERENCES projects(code),
  FOREIGN KEY (creator_code) REFERENCES users(code)
);