
Copy the following code into your PR description:

Pull request for #xxx

# Development checklist (To be completed by main developer)

- [ ] Code is working as expected
- [ ] Unit tests written and passing
- [ ] Any affected fixtures updated (`bin/cake bake fixture <TableName> -n 0 --plugin <PluginName>`)
- [ ] PR open and linked to issue
- [ ] PR using correct template
- [ ] Issue closed
- [ ] A quick self code review performed
- [ ] PR number written on card

# Code Review Checklist
## Code
 - No syntax/runtime errors and warnings in the code
 - No deprecated functions in  the code
 - There should be an explanation for any code that is commented out. “Dead Code” should be removed. If it is a temporary hack, it should be identified as such.
 - Check that each function is doing only a single thing. A function named createCustomer should not delete the existing customer and create it again
 - A method should ideally not be larger than 40 lines of code. The basic idea is always a function should be viewable in single screen without scrolling.
 - All code follows coding standard
 - No magic numbers. There should be no magic numbers like 6,10 etc… any numbers like this should be defined as a constant. And Constants should be well commented about the purpose.
 - Never allow bad code with some good comments

- [ ] Above criteria met

## Documentation
 - All methods are commented in clear language. If it is unclear to the reader, it is unclear to the user.
 - All class, variable, and method modifiers should be examined for correctness.
 - Describe behaviour for known input corner-cases.
 - Complex algorithms should be explained with references. For example,  document the reference that identifies the equation, formula, or pattern. In all cases, examine the algorithm and determine if it can be simplified.
 - Code that depends on non-obvious behaviour in external frameworks is documented with reference to external documentation. 
 - Confirm that the code does not depend on a bug in an external framework which may be fixed later, and result in an error condition. If you find a bug in an external library, open an issue, and document it in the code as necessary.
 - Units of measurement are documented for numeric values.
 - Incomplete code is marked with `//TODO` or `//FIXME` markers.
 - All public and private APIs are examined for updates.

- [ ] Above criteria met

## Unit Tests
 - Unit tests are added for each code path, and behaviour.
 - Unit tests must cover error conditions and invalid parameter cases.
 - Unit tests for standard algorithms should be examined against the standard for expected results.
 - Do not write a new algorithm for code that is already implemented in an existing public framework API, and tested.
 - Ensure that the code fixes the issue, or implements the requirement, and that the unit test confirms it. If the unit test confirms a fix for issue, add the issue number to the doc block.
 - Unit tests must have assertions
 - As a reviewer, you should understand the code. If you don’t, the review may not be complete, or the code may not be well commented.

- [ ] Above criteria met

## Error Handling
 - Invalid parameter values are handled properly early in methods (Fast Fail/Return Early).
 - Avoid using RuntimeException, or sub-classes to avoid making code changes to implement correct error handling.
 - Define and create custom Exception sub-classes to match your specific exception conditions. Document the exception in detail with example conditions so the developer understands the conditions for the exception.
 - Never ever throw a general exception with a custom message. Always try to create a custom exception class so that all the other code can handle this situation correctly.
 - **Don't pass the buck!** Don't create classes which throw Exception rather than dealing with exception condition.
 - **Don't swallow exceptions!** For example `catch (Exception ignored) {}`. It should at least log the exception.

- [ ] Above criteria met

## Performance
 - Objects are duplicated only when necessary. If you must duplicate objects, consider implementing Clone and decide if deep cloning is necessary.
 - No busy-wait loops instead of proper thread synchronisation methods. For example, avoid `while(true){ ... sleep(10);...}`
 - Avoid large objects in memory, or using String to hold large documents which should be handled with better tools. For example, don't read a large XML document into a String, or DOM.
 - Do not leave debugging code in production code.
 - "Optimisation that makes code harder to read should only be implemented if a profiler or other tool has indicated that the routine stands to gain from optimisation. These kinds of optimisations should be well documented and code that performs the same task should be preserved." - UNKNOWN.

- [ ] Above criteria met


# Testing checklist

- [ ] All unit tests pass
- [ ] Migrating from the existing Development database works (`bin/cake migrate migrate`)
- [ ] Running `migrate reset` works
- [ ] Rolling back to the current Development database works (using `bin/cake migrations rollback --target <db_version> --plugin <pluginName>`)
- [ ] All acceptance criteria are met and working as expected
- [ ] Spot checks have been performed on existing functionality that may have been affected

