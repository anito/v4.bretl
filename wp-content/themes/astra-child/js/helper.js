const animateCSS = (
  element,
  animation,
  options = {},
  callback = () => {},
  prefix = "animate__"
) => {
  return new Promise((resolve, reject) => {
    const node = document.querySelector(element);
    const repeatPrefix =
      options.repeat && (options.repeat === "infinite" ? "" : "repeat-");

    const animationName = animation && `${prefix}${animation}`;
    const durationName =
      options.duration && `${prefix}duration-${options.duration}`;
    const repeatName =
      options.repeat && `${prefix}${repeatPrefix}${options.repeat}`;
    const speedName = options.speed && `${prefix}${options.speed}`;

    animationName && node.classList.add(`${prefix}animated`, animationName);
    durationName && node.classList.add(durationName);
    repeatName && node.classList.add(repeatName);
    speedName && node.classList.add(speedName);

    // When the animation ends, we clean the classes and resolve the Promise
    function handleAnimationEnd(event) {
      event.stopPropagation();

      callback();
      animationName &&
        node.classList.remove(`${prefix}animated`, animationName);
      durationName && node.classList.remove(durationName);
      repeatName && node.classList.remove(repeatName);
      speedName && node.classList.remove(speedName);

      resolve("Animation ended");
    }

    node.addEventListener("animationend", handleAnimationEnd, { once: true });
  });
};
// We create a Promise and return it
