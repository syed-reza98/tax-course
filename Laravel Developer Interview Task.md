### **Laravel Job Interview Task Assessment**

(*After getting the task submission email, you will get 48 hours to submit the project.*)

#### **Project Overview**

Develop a course creation web page using HTML, CSS, Vanilla JavaScript, and jQuery for the frontend, and Laravel for the backend. The page should enable users to create courses with multiple modules, each containing multiple content items.

### **Design Requirements**

* **Follow the provided video instructions:**  
   [Loom](https://www.loom.com/share/8103fc8162dc45f6b92e88de67d96046?sid=7fe0d9ba-4f75-4927-868b-62e297441035)


\[ **Tips:** Correctly implement frontend and backend functionalities, including validation and data storage. While the course provides a specific scenario, you can explore alternative scenarios, ensuring these functionalities are present.

### **Project Structure**

Have a look at this scenario:

**![][image1]**

**Project Objective**

Building a Webpage includes:

* Course Creation  
* Multiple Modules (Inside the course)  
* Multiple Contents (Inside the course module)  
* Save to the database

**Tasks Breakdown**

1. Course Creation:  
   1. **Create Course Page**: Develop a simple, user-friendly web page for course creation.  
   2. **Course Fields**: Include essential input fields such as course title, description, category, Feature Video, and other related details.  
        
      ***Note:** The "Feature Video" will be an actual video file, not a video URL. Please ensure it's handled optimally for performance and compatibility.*

2. Module Section:  
   1. **Module Creation**: Provide a section to create multiple modules within a course.  
   2. **Module Fields**: Each module should have a title and any other relevant information or fields.  
   3. **Unlimited Modules**: Allow the user to add as many modules as needed for the course.  
3. Content Creation:  
   1. **Content per Module**: Inside each module, create a content section where users can add multiple types of content.  
   2. **Content Fields**: Each piece of content should have multiple related input fields such as text, images, videos, links, etc.  
   3. **Nested View**: Display content within the module as a nested structure, providing a clear visual hierarchy.  
   4. **Unlimited Content**: Users should be able to create an unlimited number of content entries within each module.  
4. Data Handling and Storage:  
   1. **Design Pattern**: Use an appropriate design pattern, ensuring that the code is maintainable and scalable.  
   2. **Frontend and Backend Validation**: Ensure proper data validation is implemented both on the frontend (e.g., field validation, required fields) and backend (e.g., data integrity, preventing invalid entries).  
   3. **Database Storage**: Store all the course, module, and content data in the database, with proper relationships between the entities.  
   4. **Error Handling**: Implement error handling for exceptions (e.g., failed validation, database errors) and provide user-friendly feedback.

**Note:** Implement best practices and ensure your app can handle large-scale data, so focus on performance and security.

**Submission Instructions**

* Upload your complete Laravel project to a **public GitHub repository**.  
* Share the **GitHub repo link** with us for review.  
* Ensure the README includes:  
  * Project setup instructions (optional)  
  * Screenshots (optional but appreciated)  
* Write a project submission email and send it to the selection email. 

*Once you've submitted your project, please allow us a few hours to thoroughly review your work. We appreciate your time and effort, and we'll get back to you with feedback or next steps as soon as possible.*

*Thank you and best of luck\! 🚀*

[image1]: <data:image/png;base64,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>