My name is Andy.  There are the rules and preferences I have for Claude or any other AI.

## Conversation and Thinking

- Speak in very brief, factual language.  Rather than paragraphs of explanation, I prefer a short summary and bullet points or tables of information.
- If you're not sure of something, say so.
- Speak as if you're a senior coworker on a tight deadline.

## Web Services

- I use MySQL for database storage
- I use PHP for web programming
- I use an Ubuntu Linux server to host these.  My local computers are also Ubuntu Linux.  Local computers can build and run local applications, but don't have database and web servers installed.
- My server's address is 10.0.0.10 when we're on my home network.  It can be SSH'd to without a password.
- I have a lightweight MVC website framework that I'd like to use for web projects.  We will review and clean it up and document it before starting to use it.
- Web applications will have API layers and Presentation layers.  They API layer will be developed first, and will be fully unit-testable.  The API layer will contain all functionality of the application, and the presentation layer is not allowed to add additional functionality by bypassing API and going directly to database, shell commands, network sockets, etc.
- I don't use ORM, even though I always use object-oriented design otherwise.
- Database tables always have an unsigned int auto_increment primary key.
- Boolean variables are always named to indicate their polarity, by including what happens when they are true in the name.  For example "power_toggle" is a bad name, "power_on" is a good name.
- Variable names are always single or plural to match what they are.  I should always be able to write something like "item = items[5]".

## Desktop Applications

- I use C++ for application programming
- I use QT whenever an application requires a GUI or can benefit from other QT libraries.
- GUI applications are split into a presentation layer and a logic layer.  The logic layer is fully unit-tested.
- GUI applications have a headless mode whenever practical, with whatever functionality makes sense for a headless application.  This is often used to test the application.  Command-line parameters can be used to control headless operation.

## GUI Style

- Always use a dark-mode display style
- Use dense layers with minimal marging around controls.  Use small fonts.  Maximum information density.
- An exception to the "maximum information density" rule when an application is designed specifically for viewing at a distance.  You'll be told when this is the case.  For this type of application we want very simple displays, huge fonts and images, full-screen operation.
- Applications should automatically save and load their window position and size.   
- When designing settings controls for applications, all settings should apply instantly, without needing an "Apply" button or closing the settings window.  Settings should automatically persist to disk and be loaded when the application starts.

## Version Control

- My projects use Git, Github, and Github CI.
- Ask me before committing work that is finished, so that I can verify that the work is correct.  Do plan on committing every finished task though.
- For large tasks you can make a branch, switch to it, and then commit checkpoints automatically.  On branches, auto-committing is fine.  On master, only commit work I have verified is correct.  When the task is finished and I have approved it, merge the branch back to master.
- Commit descriptions should be brief and information-dense.

## Code Style

- Use vertical whitespace generously to separate logical ideas.  Blank lines between every function and method.  Blank lines before and after section comments.  Each vertically grouped block of code should represent a single concept or "thought" — if two lines aren't part of the same idea, put a blank line between them.
- This vertical spacing rule applies to all languages (C++, PHP, JS, etc.) and overrides any tendency to pack short lines together.

## Refactoring

- CODE MUST STAY ORGANIZED!  This is the most important rule.  We will frequently stop to consider if our project needs to be refactored to keep it organized.  When changing code, always think about if the change fits within the current block design, or if the change is best implemented by refactoring the block-level design first.  I will often ask you about the state of a project - if it needs cleanup or refactoring or documentation to be ready for a code review.  Also consider this on your own.  Suggest these tasks whenever code is getting disorganized, very long, or lacks high-level documentation.
- Code must always have an obvious block-level design, with clear interfaces between blocks.  I prefer heirarchical organization - the top-level design is clear with a few blocks connected together.  Then in turn, the design of each block is clear - they are made of smaller blocks connected together with clear interfaces, or are raw code.  Blocks at every level are unit-testable.
- Write a notes file named `claude_notes.md` for each project you work on, with detailed technical notes that you can understand.  Use this as a way to save and load context from one session to the next.
- Write a README.md for each project.  It is designed to onboard new users by briefly introducing the application, and showing how to install and run it.  It is short and non-technical.
- Write a DEVELOPER.md for each project.  This is developer documentation which captures all information that is useful during software design: decisions, information sources, todo lists, explanations of systems, etc.  This can overlap with your notes file, it has similar information but organized and presented in a human-friendly format. 